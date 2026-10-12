# Changelog

## 2.0.0

- Canonical tenant identity only, with strict request types and path/body matching.
- Shared `InternalApi` request policy and `ApiResponse` status/error mapping for both bridges.
- Framework-independent `InternalApiException`; error values are no longer thrown.
- Atomic nonce consumption after signature verification, collision-free nonce keys,
  full-window TTL, configured clock skew, exact paths and hex-only HMAC-SHA256.
- Shared conformance fixtures and provisioning support hooks.
- Database naming is optional for adapters using unrelated provisioning helpers.

Breaking changes: update named arguments, legacy payload fields, custom nonce
stores and signer calls. Provisioning responses retain canonical `resolve_key`;
other tenant responses use `resolveKey`. See README and the v2 operational spec.
