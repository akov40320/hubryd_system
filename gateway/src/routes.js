import express from "express";
import { createLegacyClient, parseBookInfoXml, parseBookListXml, parseOverdueReportXml } from "./legacy/soapClient.js";
import { makeDigitalDb } from "./digital/db.js";
import { config } from "./config.js";

export function buildRouter() {
  const router = express.Router();
  const digitalDb = makeDigitalDb();

  // Lazy-init SOAP client (also tested at startup from server.js)
  let legacyClient = null;
  async function getLegacy() {
    if (!legacyClient) legacyClient = await createLegacyClient(config.legacyWsdlUrl);
    return legacyClient;
  }

  router.get("/physical/books", async (req, res) => {
        const inventory_number = String(req.query.inventory_number || "").trim();
    const author_name = String(req.query.author_name || "").trim();

        if (!inventory_number && !author_name) {
      return res.status(400).json({ ok: false, error: "Передай ?inventory_number=... или ?author_name=..." });
    }

    try {
      const client = await getLegacy();

            if (inventory_number) {
        const [xml] = await client.getBookByInventoryAsync(inventory_number);
        const parsed = parseBookInfoXml(xml);
        return res.json({ ok: parsed.success, message: parsed.message, book: parsed.book });
      }

      const [xml] = await client.searchBooksByAuthorAsync(author_name);
      const parsed = parseBookListXml(xml);
      return res.json({ ok: parsed.success, message: parsed.message, books: parsed.books });
    } catch (e) {
      return res.status(502).json({ ok: false, error: "Legacy SOAP недоступен", details: e.message });
    }
  });

  router.post("/physical/loan", async (req, res) => {
    const inventory_number = String(req.body.inventory_number || "").trim();
    const reader_card = String(req.body.reader_card || "").trim();

    if (!inventory_number || !reader_card) {
      return res.status(400).json({ ok: false, error: "Нужны inventory_number и reader_card" });
    }

    try {
      const client = await getLegacy();
      const [result] = await client.registerLoanAsync(inventory_number, reader_card);
      return res.json({ ok: !!result.success, result });
    } catch (e) {
      return res.status(502).json({ ok: false, error: "Legacy SOAP недоступен", details: e.message });
    }
  });

  router.get("/internal/overdue-report", async (_req, res) => {
    try {
      const resp = await fetch(config.legacyReportUrl, { headers: { "Accept": "application/xml" } });
      if (!resp.ok) throw new Error(`HTTP ${resp.status}`);
      const xml = await resp.text();
      const parsed = parseOverdueReportXml(xml);
      return res.json({ ok: true, ...parsed });
    } catch (e) {
      return res.status(502).json({ ok: false, error: "Legacy report недоступен", details: e.message });
    }
  });

  router.get("/digital/resources", async (_req, res) => {
    await digitalDb.read();
    return res.json({ ok: true, resources: digitalDb.data.DigitalResource });
  });

  router.post("/digital/download", async (req, res) => {
    const resourceId = String(req.body.resourceId || "").trim();
    const userId = String(req.body.userId || "anonymous").trim();

    if (!resourceId) return res.status(400).json({ ok: false, error: "Нужен resourceId" });

    await digitalDb.read();
    const resource = digitalDb.data.DigitalResource.find(r => r._id === resourceId);

    if (!resource) return res.status(404).json({ ok: false, error: "Ресурс не найден" });

    resource.downloadCount = Number(resource.downloadCount || 0) + 1;

    digitalDb.data.DownloadLog.push({
      _id: `log-${Date.now()}`,
      resourceId,
      userId,
      timestamp: new Date().toISOString(),
    });

    await digitalDb.write();

    // Demo link
    const fakeUrl = `/files/${encodeURIComponent(resourceId)}`;
    return res.json({ ok: true, resource, downloadUrl: fakeUrl });
  });

  return router;
}
