import { JSONFile } from "lowdb/node";
import { Low } from "lowdb";
import path from "path";
import fs from "fs";
import { fileURLToPath } from "url";

const __dirname = path.dirname(fileURLToPath(import.meta.url));

export function makeDigitalDb() {
  const file = path.join(__dirname, "../../data/digital.json");

  // Ensure directory exists (TinyDB-style JSON file storage)
  fs.mkdirSync(path.dirname(file), { recursive: true });

  const adapter = new JSONFile(file);
  const db = new Low(adapter, {
    DigitalResource: [],
    DownloadLog: [],
  });

  return db;
}
