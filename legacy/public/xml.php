<?php
declare(strict_types=1);

/**
 * Small XML helpers to keep output predictable.
 */

function xml_escape(string $value): string {
  return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}

function xml_header(): string {
  return '<?xml version="1.0" encoding="UTF-8"?>';
}
