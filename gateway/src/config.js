import dotenv from "dotenv";
dotenv.config();

export const config = {
  port: Number(process.env.PORT || 3000),

  // Legacy PHP SOAP WSDL URL (default for local run with PHP built-in server on :8080)
  legacyWsdlUrl:
    process.env.LEGACY_SOAP_WSDL_URL ||
    "http://127.0.0.1:8080/soap-server.php?wsdl",

  // Legacy overdue report (raw XML, without XSLT)
  legacyReportUrl:
    process.env.LEGACY_REPORT_URL ||
    "http://127.0.0.1:8080/report.php?type=overdue&raw=1",
};
