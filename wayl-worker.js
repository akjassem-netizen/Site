export default {
  async fetch(request) {
    const headers = {
      "Access-Control-Allow-Origin": "https://akjassem.com",
      "Access-Control-Allow-Methods": "POST, OPTIONS",
      "Access-Control-Allow-Headers": "Content-Type",
      "Content-Type": "application/json"
    };
    if (request.method === "OPTIONS") return new Response(null, { headers });
    try {
      const { waylKey, title, amountIQD } = await request.json();
      const res = await fetch("https://api.thewayl.com/api/v1/links", {
        method: "POST",
        headers: { "Content-Type": "application/json", "X-WAYL-AUTHENTICATION": waylKey },
        body: JSON.stringify({
          referenceId: "AKH-" + Date.now(),
          total: Number(amountIQD),
          currency: "IQD",
          lineItem: [{ label: title, amount: Number(amountIQD), type: "increase" }],
          redirectionUrl: "https://akjassem.com/payment/?status=success"
        })
      });
      return new Response(await res.text(), { status: res.status, headers });
    } catch (e) {
      return new Response(JSON.stringify({ message: e.message }), { status: 500, headers });
    }
  }
};
