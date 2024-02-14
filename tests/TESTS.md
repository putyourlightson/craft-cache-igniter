# Test Specification

This document outlines the test specification for the Cache Igniter plugin.

---

## Architecture Tests

### [Architecture](pest/Architecture/ArchitectureTest.php)

_Tests the architecture of the plugin._

![Pass](https://raw.githubusercontent.com/putyourlightson/craft-generate-test-spec/main/icons/pass.svg) Source code does not contain any “dump or die” statements.  

## Feature Tests

### [Refresh](pest/Feature/RefreshTest.php)

_Tests refreshing site URIs._

![Pass](https://raw.githubusercontent.com/putyourlightson/craft-generate-test-spec/main/icons/pass.svg) Refreshing a site URI with a matching included refresh URI pattern creates a record and a queue job.  
![Pass](https://raw.githubusercontent.com/putyourlightson/craft-generate-test-spec/main/icons/pass.svg) Refreshing a site URI with a matching excluded refresh URI pattern does not create a record nor a queue job.  

### [Warm](pest/Feature/WarmTest.php)

_Tests warming URLs._

![Pass](https://raw.githubusercontent.com/putyourlightson/craft-generate-test-spec/main/icons/pass.svg) Warming URLs with a progress handler creates records.  
![Pass](https://raw.githubusercontent.com/putyourlightson/craft-generate-test-spec/main/icons/pass.svg) Warming a URL longer than the max URL length does not create a record.  
![Pass](https://raw.githubusercontent.com/putyourlightson/craft-generate-test-spec/main/icons/pass.svg) Warming URLs without a progress handler creates a record and a queue job.  

### [Warmer](pest/Feature/WarmerTest.php)

_Tests warming URLs with each of the warmer drivers._

![Pass](https://raw.githubusercontent.com/putyourlightson/craft-generate-test-spec/main/icons/pass.svg) Warming a URL results in a positive response from the API with data set `GlobalPingWarmer`.  
![Pass](https://raw.githubusercontent.com/putyourlightson/craft-generate-test-spec/main/icons/pass.svg) Warming a URL results in a positive response from the API with data set `HttpWarmer`.  
![Pass](https://raw.githubusercontent.com/putyourlightson/craft-generate-test-spec/main/icons/pass.svg) Warming a URL results in a positive response from the API with data set `DummyWarmer`.  
![Pass](https://raw.githubusercontent.com/putyourlightson/craft-generate-test-spec/main/icons/pass.svg) Fetching the rate limit from the API returns a valid result with data set `GlobalPingWarmer`.  
![Pass](https://raw.githubusercontent.com/putyourlightson/craft-generate-test-spec/main/icons/pass.svg) Fetching the rate limit from the API returns a valid result with data set `HttpWarmer`.  
![Pass](https://raw.githubusercontent.com/putyourlightson/craft-generate-test-spec/main/icons/pass.svg) Fetching the rate limit from the API returns a valid result with data set `DummyWarmer`.  
