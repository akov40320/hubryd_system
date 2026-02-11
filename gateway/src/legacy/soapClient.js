import soap from "soap";
import { XMLParser } from "fast-xml-parser";

const xmlParser = new XMLParser({
  ignoreAttributes: false,
  attributeNamePrefix: "@_",
});

export async function createLegacyClient(wsdlUrl) {
    // Пакет `soap` умеет работать напрямую с URL WSDL.
  return soap.createClientAsync(wsdlUrl, {
    endpoint: wsdlUrl.replace(/\/library\.wsdl$/i, "/soap-server.php"),
    wsdl_options: { timeout: 5000 },
  });
}

export function parseBookInfoXml(xml) {
  const obj = xmlParser.parse(xml);
    // Пример структуры распарсенного XML: { BookInfo: { success: 'true', book: {...} } }
  const root = obj?.BookInfo;
  if (!root) return { success: false, message: "Bad XML", book: null };
  const success = String(root.success) === "true";
  if (!success) return { success, message: root.message || "Error", book: null };
  return { success, message: "OK", book: root.book };
}

export function parseBookListXml(xml) {
  const obj = xmlParser.parse(xml);
  const root = obj?.BookList;
  if (!root) return { success: false, message: "Bad XML", books: [] };
  const success = String(root.success) === "true";
  if (!success) return { success, message: root.message || "Error", books: [] };
  const booksRaw = root.books?.book ?? [];
  const books = Array.isArray(booksRaw) ? booksRaw : [booksRaw];
  return { success, message: "OK", books };
}

export function parseOverdueReportXml(xml) {
  const obj = xmlParser.parse(xml);
  const root = obj?.OverdueReport;
  const itemsRaw = root?.items?.item ?? [];
  const items = Array.isArray(itemsRaw) ? itemsRaw : [itemsRaw];
  return {
    generated_at: root?.generated_at || null,
    items,
  };
}
