# Test Specification

This document outlines the test specification for the Cache Igniter plugin.

---

## Feature Tests

### [GlobalPing](pest/Feature/GlobalPingTest.php)

_Tests warming URLs._

![Pass](https://raw.githubusercontent.com/putyourlightson/craft-generate-test-spec/main/icons/pass.svg) Warming a URL results in a positive response from the API.  
![Pass](https://raw.githubusercontent.com/putyourlightson/craft-generate-test-spec/main/icons/pass.svg) Fetching the rate limit from the API returns a valid result.  

### [Refresh](pest/Feature/RefreshTest.php)

_Tests refreshing site URIs._

![Pass](https://raw.githubusercontent.com/putyourlightson/craft-generate-test-spec/main/icons/pass.svg) Refreshing a site URI with a matching a refresh site URI pattern creates a record and a queue job.  

### [Warm](pest/Feature/WarmTest.php)

_Tests warming URLs._

![Pass](https://raw.githubusercontent.com/putyourlightson/craft-generate-test-spec/main/icons/pass.svg) Warming URLs without a progress handler creates a record and a queue job.  
