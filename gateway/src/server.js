import express from "express";
import cors from "cors";
import path from "path";
import { fileURLToPath } from "url";

import { config } from "./config.js";
import { buildRouter } from "./routes.js";
import { createLegacyClient } from "./legacy/soapClient.js";
import { makeDigitalDb } from "./digital/db.js";

const __dirname = path.dirname(fileURLToPath(import.meta.url));

const app = express();
app.use(cors());
app.use(express.json({ limit: "1mb" }));

// Serve frontend
app.use(express.static(path.join(__dirname, "../public")));

app.use("/api", buildRouter());

// Health
app.get("/healthz", (_req, res) => res.json({ ok: true }));

// Startup checks
async function startup() {
  // Ensure digital db exists
  const digitalDb = makeDigitalDb();
  await digitalDb.read();
  if (!digitalDb.data) {
    digitalDb.data = { DigitalResource: [], DownloadLog: [] };
    await digitalDb.write();
  }

  // Check SOAP availability
  try {
    const client = await createLegacyClient(config.legacyWsdlUrl);
    // Test call to ensure SOAP is reachable
    await client.getBookByInventoryAsync("LIB-2024-001");
    console.log("[startup] Legacy SOAP OK");
  } catch (e) {
    console.warn("[startup] Legacy SOAP check failed:", e.message);
  }

  app.listen(config.port, () => {
    console.log(`Gateway listening on http://localhost:${config.port}`);
  });
}

startup();
