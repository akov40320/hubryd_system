import dotenv from "dotenv";
dotenv.config();

export const config = {
  port: Number(process.env.PORT || 3000),

    // URL WSDL для легаси SOAP (по умолчанию: локальный запуск на PHP-сервере :8080)
  legacyWsdlUrl:
    process.env.LEGACY_SOAP_WSDL_URL ||
    "http://127.0.0.1:8080/soap-server.php?wsdl",

    // Легаси-отчёт по просрочкам (сырой XML, без XSLT)
  legacyReportUrl:
    process.env.LEGACY_REPORT_URL ||
    "http://127.0.0.1:8080/report.php?type=overdue&raw=1",
};
