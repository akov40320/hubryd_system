import { makeDigitalDb } from "./db.js";

const db = makeDigitalDb();
await db.read();

if (db.data.DigitalResource.length > 0) {
  console.log(`Already seeded: ${db.data.DigitalResource.length} resources`);
  process.exit(0);
}

db.data.DigitalResource = [
  { _id: "res-1", title: "Clean Code (PDF)", author: "Robert C. Martin", format: "pdf", fileSize: 5242880, tags: ["programming"], downloadCount: 0 },
  { _id: "res-2", title: "The Pragmatic Programmer (EPUB)", author: "Andrew Hunt", format: "epub", fileSize: 3145728, tags: ["programming"], downloadCount: 0 },
  { _id: "res-3", title: "Sapiens (MP3)", author: "Yuval Noah Harari", format: "mp3", fileSize: 104857600, tags: ["history","audiobook"], downloadCount: 0 }
];

db.data.DownloadLog = [];
await db.write();

console.log("Seeded digital.json with 3 resources");
