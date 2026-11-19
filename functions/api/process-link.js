export async function onRequestPost(context) {
  try {
    const body = await context.request.json();
    const { waylKey, title, amountIQD } = body;

    if (!waylKey || !title || !amountIQD) {
      return new Response(JSON.stringify({ error: "بيانات الدفع غير مكتملة" }), {
        status: 400,
        headers: { "Content-Type": "application/json" }
      });
    }

    const waylRes = await fetch("https://api.thewayl.com/api/v1/links", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "X-WAYL-AUTHENTICATION": waylKey
      },
      body: JSON.stringify({
        referenceId: "AKH-" + Date.now(),
        total: Number(amountIQD),
        currency: "IQD",
        lineItem: [
          {
            label: title,
            amount: Number(amountIQD),
            type: "increase"
          }
        ],
        redirectionUrl: "https://akjassem.com/payment/?status=success"
      })
    });

    const data = await waylRes.json();

    if (!waylRes.ok) {
      return new Response(JSON.stringify({ error: data.message || "خطأ من بوابة الدفع", details: data }), {
        status: waylRes.status,
        headers: { "Content-Type": "application/json" }
      });
    }

    const checkoutUrl = data.data?.url || data.url || data.paymentUrl;
    return new Response(JSON.stringify({ url: checkoutUrl }), {
      status: 200,
      headers: { "Content-Type": "application/json" }
    });

  } catch (err) {
    return new Response(JSON.stringify({ error: err.message }), {
      status: 500,
      headers: { "Content-Type": "application/json" }
    });
  }
}
