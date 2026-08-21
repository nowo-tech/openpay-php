# Code inventory — Openpay PHP SDK

Every production PHP file under `Openpay/` and root `Openpay.php` (100%).

| File | Role |
| ---- | ---- |
| `Openpay.php` | Manual-install autoload bootstrap |
| `Openpay/Data/CurlHttpTransport.php` | Default cURL HTTP transport |
| `Openpay/Data/Openpay.php` | SDK facade, credentials, VERSION |
| `Openpay/Data/OpenpayApi.php` | Root API client |
| `Openpay/Data/OpenpayApiAuthError.php` | Auth error |
| `Openpay/Data/OpenpayApiConnectionError.php` | Connection error |
| `Openpay/Data/OpenpayApiConnector.php` | HTTP connector singleton |
| `Openpay/Data/OpenpayApiConsole.php` | Console / logging helper |
| `Openpay/Data/OpenpayApiDerivedResource.php` | Nested resource list |
| `Openpay/Data/OpenpayApiError.php` | Base API error |
| `Openpay/Data/OpenpayApiRequestError.php` | Request error |
| `Openpay/Data/OpenpayApiResourceBase.php` | Resource CRUD base |
| `Openpay/Data/OpenpayApiTransactionError.php` | Transaction error |
| `Openpay/Data/OpenpayHttpTransport.php` | HTTP transport interface |
| `Openpay/Data/OpenpaySession.php` | Request-scoped session |
| `Openpay/Resources/OpenpayBankAccount.php` | Bank account resource |
| `Openpay/Resources/OpenpayBankAccountList.php` | Bank account list |
| `Openpay/Resources/OpenpayBine.php` | BIN resource |
| `Openpay/Resources/OpenpayCapture.php` | Capture resource |
| `Openpay/Resources/OpenpayCard.php` | Card resource |
| `Openpay/Resources/OpenpayCardList.php` | Card list |
| `Openpay/Resources/OpenpayCharge.php` | Charge resource |
| `Openpay/Resources/OpenpayChargeList.php` | Charge list |
| `Openpay/Resources/OpenpayCustomer.php` | Customer resource |
| `Openpay/Resources/OpenpayCustomerList.php` | Customer list |
| `Openpay/Resources/OpenpayFee.php` | Fee resource |
| `Openpay/Resources/OpenpayFeeList.php` | Fee list |
| `Openpay/Resources/OpenpayPayout.php` | Payout resource |
| `Openpay/Resources/OpenpayPayoutList.php` | Payout list |
| `Openpay/Resources/OpenpayPlan.php` | Plan resource |
| `Openpay/Resources/OpenpayPlanList.php` | Plan list |
| `Openpay/Resources/OpenpayPse.php` | PSE resource |
| `Openpay/Resources/OpenpayPseList.php` | PSE list |
| `Openpay/Resources/OpenpayRefund.php` | Refund resource |
| `Openpay/Resources/OpenpaySubscription.php` | Subscription resource |
| `Openpay/Resources/OpenpaySubscriptionList.php` | Subscription list |
| `Openpay/Resources/OpenpayToken.php` | Token resource |
| `Openpay/Resources/OpenpayTransfer.php` | Transfer resource |
| `Openpay/Resources/OpenpayTransferList.php` | Transfer list |
| `Openpay/Resources/OpenpayWebhook.php` | Webhook resource |
