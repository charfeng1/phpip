# Task: Mitigate XXE in OPSService XML parsing

## Summary
The OPSService currently calls `simplexml_load_string` with `LIBXML_NOENT`, which expands external entities and enables XXE attacks. Remove entity expansion and disable external network access when parsing OPS responses.

## Required changes
- Update `app/Services/OPSService.php` to parse XML safely:
  - Drop the `LIBXML_NOENT` flag and add safe flags such as `LIBXML_NONET | LIBXML_NOBLANKS | LIBXML_NOCDATA`.
  - Temporarily disable entity loading with `libxml_disable_entity_loader(true)` during parsing.
  - Add error handling if the XML fails to load with the stricter settings.
- Consider switching to `DOMDocument` or a streaming parser if SimpleXML cannot meet the security requirements without NOENT.

## Acceptance criteria
- `simplexml_load_string` is no longer called with `LIBXML_NOENT`.
- External entity expansion is disabled, and the parser rejects malicious payloads.
- Existing features (procedural steps and legal status retrieval) continue working under the safer parser.
