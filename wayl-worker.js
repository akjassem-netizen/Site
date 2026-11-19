export default {
  async fetch(request, env) {
    const headers = {
      "Access-Control-Allow-Origin": "https://akjassem.com",
      "Access-Control-Allow-Methods": "GET, POST, OPTIONS",
      "Access-Control-Allow-Headers": "Content-Type",
      "Content-Type": "application/json"
    };

    if (request.method === "OPTIONS") return new Response(null, { headers });

    const url = new URL(request.url);

    // 1. جلب بيانات الفاتورة النظيفة عبر الرقم المرجعي (?ref=AKH-...)
    if (request.method === "GET") {
      const ref = url.searchParams.get("ref");
      if (!ref) {
        return new Response(JSON.stringify({ message: "Missing ref" }), { status: 400, headers });
      }
      if (!env.AKH_KV) {
        return new Response(JSON.stringify({ message: "KV not bound" }), { status: 500, headers });
      }
      const record = await env.AKH_KV.get("inv_" + ref);
      if (!record) {
        return new Response(JSON.stringify({ message: "Invoice not found" }), { status: 404, headers });
      }
      return new Response(record, { status: 200, headers });
    }

    // 2. معالجة الطلبات (إنشاء فاتورة جديدة أو توثيق موافقة المراجع أو تثبيت الدفع)
    if (request.method === "POST") {
      try {
        const body = await request.json();

        // أ) توثيق موافقة المراجع على السياسات الثلاث بالوقت والتاريخ
        if (body.action === "consent" && body.ref) {
          if (env.AKH_KV) {
            const existingRaw = await env.AKH_KV.get("inv_" + body.ref);
            if (existingRaw) {
              const existing = JSON.parse(existingRaw);
              existing.consent = {
                agreed: true,
                timestamp: new Date().toISOString(),
                localTime: body.localTime || new Date().toLocaleString("ar-IQ"),
                lang: body.lang || "ar",
                userAgent: request.headers.get("User-Agent") || "",
                ip: request.headers.get("CF-Connecting-IP") || ""
              };
              await env.AKH_KV.put("inv_" + body.ref, JSON.stringify(existing));
              return new Response(JSON.stringify({ ok: true, consent: existing.consent }), { status: 200, headers });
            }
          }
          return new Response(JSON.stringify({ ok: false }), { status: 200, headers });
        }

        // ب) تحديث حالة الفاتورة إلى مدفوعة بعد العودة الناجحة
        if (body.action === "markPaid" && body.ref) {
          if (env.AKH_KV) {
            const existingRaw = await env.AKH_KV.get("inv_" + body.ref);
            if (existingRaw) {
              const existing = JSON.parse(existingRaw);
              existing.status = "paid";
              existing.paidAt = new Date().toISOString();
              await env.AKH_KV.put("inv_" + body.ref, JSON.stringify(existing));
            }
          }
          return new Response(JSON.stringify({ ok: true }), { status: 200, headers });
        }

        // ج) إنشاء رابط دفع جديد عبر Wayl وحفظ الفاتورة بالرقم المرجعي
        const { waylKey, title, amountIQD, invoiceMeta } = body;
        const refId = (invoiceMeta && invoiceMeta.ref) ? invoiceMeta.ref : ("AKH-" + Date.now());

        const res = await fetch("https://api.thewayl.com/api/v1/links", {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
            "X-WAYL-AUTHENTICATION": waylKey
          },
          body: JSON.stringify({
            referenceId: refId,
            total: Number(amountIQD),
            currency: "IQD",
            lineItem: [
              {
                label: title,
                amount: Number(amountIQD),
                type: "increase",
                image: "https://akjassem.com/favicon.ico"
              }
            ],
            webhookUrl: "https://akh-payment.akjassem.workers.dev",
            webhookSecret: "akh_secret_2026",
            redirectionUrl: `https://akjassem.com/payment/?status=success&ref=${encodeURIComponent(refId)}`
          })
        });

        const text = await res.text();
        if (!res.ok) {
          return new Response(JSON.stringify({ message: text }), { status: res.status, headers });
        }

        const parsedWayl = JSON.parse(text);
        const payUrl = parsedWayl.data?.url || parsedWayl.url || parsedWayl.paymentUrl || "";

        // حفظ بيانات الفاتورة في KV ليصبح الرابط قصيراً (?ref=AKH-...)
        if (env.AKH_KV && payUrl) {
          const fullRecord = {
            ...(invoiceMeta || {}),
            ref: refId,
            u: payUrl,
            a: Number(amountIQD),
            createdAt: new Date().toISOString(),
            status: "pending"
          };
          await env.AKH_KV.put("inv_" + refId, JSON.stringify(fullRecord));
        }

        return new Response(text, { status: res.status, headers });
      } catch (e) {
        return new Response(JSON.stringify({ message: e.message }), { status: 500, headers });
      }
    }

    return new Response(JSON.stringify({ message: "Method not allowed" }), { status: 405, headers });
  }
};
