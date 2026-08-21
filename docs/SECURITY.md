# Security

## Reporting a vulnerability

**Do not** open a public GitHub issue for security reports.

Email **hectorfranco@nowo.tech** with:

- Type of issue
- Affected files / commit
- Reproduction steps
- Impact

See also the public policy in [.github/SECURITY.md](../.github/SECURITY.md).

## Analysis (this library)

| Area | Notes |
| ---- | ----- |
| Credentials | Process statics. Must `reset()` / `OpenpaySession` on shared workers. |
| HTTP | Default cURL; injectable `OpenpayHttpTransport`. Timeouts on connect/total. |
| TLS | OS trust store (no bundled `cacert.pem`). |
| PCI | Do not log request/response bodies that may contain PAN. |
| Secrets | Tests use fake transports. Never commit live `sk_` keys. |

## Secrets

Do not commit `.env`, merchant private keys, or cardholder data.
