# Code inventory — Openpay PHP SDK

**Last audited**: 2026-08-21  
**Layout**: no `src/`; production PHP lives in `Openpay/` (PSR-4) plus root `Openpay.php` (manual-install bootstrap).  
**Audit command**: `find Openpay -name '*.php' | wc -l` → **39**; plus `Openpay.php` → **40** production units.

## Coverage summary

| Category | Units | FR prefix |
| -------- | ----- | --------- |
| Bootstrap / facade / session | 4 | FR-SDK-* |
| HTTP + errors | 9 | FR-HTTP-* / FR-ERR-* |
| Resource engine | 2 | FR-RES-001 / FR-RES-002 |
| Domain resources | 25 | FR-RES-003 |
| **Total** | **40** | |

Clover (PHPUnit `Openpay/` include): statements **≥ 99%**. Root `Openpay.php` is bootstrap-only (not in the Clover include). Two unreachable Connector statements are `@codeCoverageIgnore` (missing-class guard; return after throw). See [`docs/COVERAGE.md`](../../docs/COVERAGE.md).

## Bootstrap / facade / session

| File | Requirement |
| ---- | ----------- |
| `Openpay.php` | FR-SDK-001 |
| `Openpay/Data/Openpay.php` | FR-SDK-002 |
| `Openpay/Data/OpenpaySession.php` | FR-SDK-003 |
| `Openpay/Data/OpenpayApi.php` | FR-SDK-004 |

## HTTP + errors

| File | Requirement |
| ---- | ----------- |
| `Openpay/Data/OpenpayHttpTransport.php` | FR-HTTP-002 |
| `Openpay/Data/CurlHttpTransport.php` | FR-HTTP-001 / FR-ERR-002 |
| `Openpay/Data/OpenpayApiConnector.php` | FR-HTTP-002 / FR-HTTP-003 / FR-ERR-001 |
| `Openpay/Data/OpenpayApiConsole.php` | FR-HTTP-003 |
| `Openpay/Data/OpenpayApiError.php` | FR-ERR-001 |
| `Openpay/Data/OpenpayApiAuthError.php` | FR-ERR-001 |
| `Openpay/Data/OpenpayApiConnectionError.php` | FR-ERR-002 |
| `Openpay/Data/OpenpayApiRequestError.php` | FR-ERR-001 |
| `Openpay/Data/OpenpayApiTransactionError.php` | FR-ERR-001 |

## Resource engine

| File | Requirement |
| ---- | ----------- |
| `Openpay/Data/OpenpayApiResourceBase.php` | FR-RES-001 |
| `Openpay/Data/OpenpayApiDerivedResource.php` | FR-RES-002 (cache ids cast to string before `strtolower`) |

## Domain resources (FR-RES-003)

| File | Role |
| ---- | ---- |
| `Openpay/Resources/OpenpayBankAccount.php` | Bank account |
| `Openpay/Resources/OpenpayBankAccountList.php` | Bank account list |
| `Openpay/Resources/OpenpayBine.php` | BIN lookup |
| `Openpay/Resources/OpenpayCapture.php` | Charge capture |
| `Openpay/Resources/OpenpayCard.php` | Card |
| `Openpay/Resources/OpenpayCardList.php` | Card list |
| `Openpay/Resources/OpenpayCharge.php` | Charge |
| `Openpay/Resources/OpenpayChargeList.php` | Charge list |
| `Openpay/Resources/OpenpayCustomer.php` | Customer |
| `Openpay/Resources/OpenpayCustomerList.php` | Customer list |
| `Openpay/Resources/OpenpayFee.php` | Fee |
| `Openpay/Resources/OpenpayFeeList.php` | Fee list |
| `Openpay/Resources/OpenpayPayout.php` | Payout |
| `Openpay/Resources/OpenpayPayoutList.php` | Payout list |
| `Openpay/Resources/OpenpayPlan.php` | Plan |
| `Openpay/Resources/OpenpayPlanList.php` | Plan list |
| `Openpay/Resources/OpenpayPse.php` | PSE |
| `Openpay/Resources/OpenpayPseList.php` | PSE list |
| `Openpay/Resources/OpenpayRefund.php` | Refund |
| `Openpay/Resources/OpenpaySubscription.php` | Subscription |
| `Openpay/Resources/OpenpaySubscriptionList.php` | Subscription list |
| `Openpay/Resources/OpenpayToken.php` | Token |
| `Openpay/Resources/OpenpayTransfer.php` | Transfer |
| `Openpay/Resources/OpenpayTransferList.php` | Transfer list |
| `Openpay/Resources/OpenpayWebhook.php` | Webhook |
