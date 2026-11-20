export default {
  async fetch(request, env) {
    const corsHeaders = {
      "Access-Control-Allow-Origin": "https://akjassem.com",
      "Access-Control-Allow-Methods": "GET, POST, OPTIONS",
      "Access-Control-Allow-Headers": "Content-Type, X-WAYL-SIGNATURE, X-WEBHOOK-SECRET",
      "Content-Type": "application/json; charset=utf-8"
    };

    if (request.method === "OPTIONS") {
      return new Response(null, { status: 204, headers: corsHeaders });
    }

    const sanitizeRef = (raw) => {
      const cleaned = String(raw || "").trim();
      return /^[A-Za-z0-9_-]{4,64}$/.test(cleaned) ? cleaned : "";
    };

    const url = new URL(request.url);

    // 1. جلب بيانات الفاتورة عبر الرقم المرجعي (?ref=AKH-...)
    if (request.method === "GET") {
      const ref = sanitizeRef(url.searchParams.get("ref"));
      if (!ref) {
        return new Response(JSON.stringify({ message: "Missing or invalid ref" }), { status: 400, headers: corsHeaders });
      }
      if (!env.AKH_KV) {
        return new Response(JSON.stringify({ message: "KV not bound" }), { status: 500, headers: corsHeaders });
      }

      const recordRaw = await env.AKH_KV.get("inv_" + ref);
      if (!recordRaw) {
        return new Response(JSON.stringify({ message: "Invoice not found" }), { status: 404, headers: corsHeaders });
      }

      try {
        const record = JSON.parse(recordRaw);
        if (
          record.status === "pending" &&
          record.expiresAt &&
          Number(record.expiresAt) > 0 &&
          Date.now() > Number(record.expiresAt)
        ) {
          record.status = "cancelled";
          record.expiredAt = new Date().toISOString();
          await env.AKH_KV.put("inv_" + ref, JSON.stringify(record));
        }
        return new Response(JSON.stringify(record), { status: 200, headers: corsHeaders });
      } catch (e) {
        return new Response(recordRaw, { status: 200, headers: corsHeaders });
      }
    }

    // 2. معالجة الطلبات (Webhook / إنشاء فاتورة / توثيق موافقة / تحديث حالة)
    if (request.method === "POST") {
      try {
        const body = await request.json();
        const expectedSecret = env.WEBHOOK_SECRET || "akh_secret_2026";

        // استقبال إشعار Webhook من Wayl عند إتمام الدفع تلقائياً
        const webhookRef = sanitizeRef(
          body.referenceId ||
          body.data?.referenceId ||
          body.payment?.referenceId ||
          ""
        );
        const webhookStatus = String(
          body.status || body.data?.status || body.event || ""
        ).toLowerCase();

        if (!body.action && !body.waylKey && webhookRef) {
          const incomingSecret =
            request.headers.get("X-WEBHOOK-SECRET") ||
            request.headers.get("X-WAYL-SIGNATURE") ||
            body.webhookSecret ||
            body.secret ||
            "";

          if (incomingSecret && incomingSecret !== expectedSecret) {
            return new Response(JSON.stringify({ ok: false, message: "Unauthorized webhook" }), { status: 401, headers: corsHeaders });
          }

          if (
            webhookStatus.includes("paid") ||
            webhookStatus.includes("success") ||
            webhookStatus.includes("completed")
          ) {
            if (env.AKH_KV) {
              const existingRaw = await env.AKH_KV.get("inv_" + webhookRef);
              if (existingRaw) {
                const existing = JSON.parse(existingRaw);
                if (existing.status !== "paid") {
                  existing.status = "paid";
                  existing.paidAt = new Date().toISOString();
                  existing.paidVia = "webhook";
                  await env.AKH_KV.put("inv_" + webhookRef, JSON.stringify(existing));
                }
              }
            }
          }
          return new Response(JSON.stringify({ ok: true, ref: webhookRef }), { status: 200, headers: corsHeaders });
        }

        // أ) توثيق موافقة المراجع على السياسات الثلاث بالوقت والتاريخ
        if (body.action === "consent") {
          const ref = sanitizeRef(body.ref);
          if (!ref) {
            return new Response(JSON.stringify({ ok: false, message: "Invalid ref" }), { status: 400, headers: corsHeaders });
          }
          if (env.AKH_KV) {
            const existingRaw = await env.AKH_KV.get("inv_" + ref);
            if (existingRaw) {
              const existing = JSON.parse(existingRaw);
              existing.consent = {
                agreed: true,
                timestamp: new Date().toISOString(),
                localTime: String(body.localTime || new Date().toLocaleString("ar-IQ")).slice(0, 80),
                lang: body.lang === "en" ? "en" : "ar",
                userAgent: (request.headers.get("User-Agent") || "").slice(0, 250),
                ip: (request.headers.get("CF-Connecting-IP") || "").slice(0, 64)
              };
              await env.AKH_KV.put("inv_" + ref, JSON.stringify(existing));
              return new Response(JSON.stringify({ ok: true, consent: existing.consent }), { status: 200, headers: corsHeaders });
            }
          }
          return new Response(JSON.stringify({ ok: false }), { status: 200, headers: corsHeaders });
        }

        // ب) تحديث حالة الفاتورة إلى مدفوعة بعد العودة الناجحة
        if (body.action === "markPaid") {
          const ref = sanitizeRef(body.ref);
          if (!ref) {
            return new Response(JSON.stringify({ ok: false, message: "Invalid ref" }), { status: 400, headers: corsHeaders });
          }
          if (env.AKH_KV) {
            const existingRaw = await env.AKH_KV.get("inv_" + ref);
            if (existingRaw) {
              const existing = JSON.parse(existingRaw);
              if (existing.status !== "cancelled" && existing.status !== "paid") {
                existing.status = "paid";
                existing.paidAt = new Date().toISOString();
                await env.AKH_KV.put("inv_" + ref, JSON.stringify(existing));
              }
            }
          }
          return new Response(JSON.stringify({ ok: true }), { status: 200, headers: corsHeaders });
        }

        // ج) تحديث حالة الفاتورة غير المدفوعة إلى ملغاة / متروكة
        if (body.action === "markCancelled") {
          const ref = sanitizeRef(body.ref);
          if (!ref) {
            return new Response(JSON.stringify({ ok: false, message: "Invalid ref" }), { status: 400, headers: corsHeaders });
          }
          if (env.AKH_KV) {
            const existingRaw = await env.AKH_KV.get("inv_" + ref);
            if (existingRaw) {
              const existing = JSON.parse(existingRaw);
              if (existing.status !== "paid") {
                existing.status = "cancelled";
                existing.cancelledAt = new Date().toISOString();
                await env.AKH_KV.put("inv_" + ref, JSON.stringify(existing));
              }
            }
          }
          return new Response(JSON.stringify({ ok: true }), { status: 200, headers: corsHeaders });
        }

        // د) إنشاء رابط دفع جديد عبر Wayl وحفظ الفاتورة بالرقم المرجعي
        const waylKey = String(body.waylKey || "").trim();
        const title = String(body.title || "").trim().slice(0, 180);
        const amountIQD = Math.round(Number(body.amountIQD || 0));
        const invoiceMeta = body.invoiceMeta && typeof body.invoiceMeta === "object" ? body.invoiceMeta : {};

        if (!waylKey || !title || !Number.isFinite(amountIQD) || amountIQD < 1000) {
          return new Response(
            JSON.stringify({ message: "بيانات إنشاء الفاتورة غير مكتملة أو المبلغ أقل من 1000 دينار." }),
            { status: 400, headers: corsHeaders }
          );
        }

        const refId = sanitizeRef(invoiceMeta.ref) || ("AKH-" + Date.now());

        const res = await fetch("https://api.thewayl.com/api/v1/links", {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
            "X-WAYL-AUTHENTICATION": waylKey
          },
          body: JSON.stringify({
            referenceId: refId,
            total: amountIQD,
            currency: "IQD",
            lineItem: [
              {
                label: title,
                amount: amountIQD,
                type: "increase",
                image: "https://akjassem.com/og-logo.jpg"
              }
            ],
            webhookUrl: "https://akh-payment.akjassem.workers.dev",
            webhookSecret: expectedSecret,
            redirectionUrl: `https://akjassem.com/payment/?status=success&ref=${encodeURIComponent(refId)}`
          })
        });

        const text = await res.text();
        if (!res.ok) {
          return new Response(JSON.stringify({ message: text }), { status: res.status, headers: corsHeaders });
        }

        const parsedWayl = JSON.parse(text);
        const payUrl =
          parsedWayl.data?.url ||
          parsedWayl.data?.link ||
          parsedWayl.url ||
          parsedWayl.link ||
          parsedWayl.paymentUrl ||
          "";

        // حفظ بيانات الفاتورة الكاملة في KV ليصبح الرابط قصيراً (?ref=AKH-...)
        if (env.AKH_KV && payUrl) {
          const fullRecord = {
            ...invoiceMeta,
            ref: refId,
            u: payUrl,
            a: amountIQD,
            createdAt: new Date().toISOString(),
            status: "pending"
          };
          await env.AKH_KV.put("inv_" + refId, JSON.stringify(fullRecord));
        }

        return new Response(text, { status: res.status, headers: corsHeaders });
      } catch (e) {
        return new Response(JSON.stringify({ message: e.message }), { status: 500, headers: corsHeaders });
      }
    }

    return new Response(JSON.stringify({ message: "Method not allowed" }), { status: 405, headers: corsHeaders });
  }
};
