# GitHub Actions Runner Selection Guidelines

This document provides guidelines for selecting appropriate GitHub Actions runners based on workflow resource requirements. Following these guidelines helps optimize costs and improve workflow efficiency.

## Runner Types and Specifications

### Standard Runners (2-core)
- **Runner Label**: `ubuntu-latest`
- **Specifications**: 2 CPU cores, 7 GB RAM, 14 GB SSD
- **Cost**: Included in standard GitHub plan

### Large Runners (4-core)
- **Runner Label**: `ubuntu-latest-4-core`
- **Specifications**: 4 CPU cores, 16 GB RAM, 14 GB SSD
- **Cost**: 2x standard runner cost

### Extra Large Runners (8-core)
- **Runner Label**: `ubuntu-latest-8-core`
- **Specifications**: 8 CPU cores, 32 GB RAM, 14 GB SSD
- **Cost**: 4x standard runner cost

## Workflow Resource Categorization

### Lightweight Workflows (Standard 2-core Runner)
Use `ubuntu-latest` for:
- Code linting and style checks (Pint, ESLint, Prettier)
- Translation validation
- Documentation builds
- Simple compilation tasks
- Workflows completing in < 10 minutes with minimal dependencies

**Example workflows in this repository**:
- `pint_tests.yml` - PHP code style checking
- `translation_tests.yml` - Translation file validation

**Timeout recommendation**: 10-15 minutes

### Medium Workflows (Standard 2-core Runner)
Use `ubuntu-latest` for:
- Unit and feature tests with database services
- Integration tests (non-browser)
- Build tasks with moderate complexity
- Workflows completing in 10-30 minutes

**Example workflows in this repository**:
- `pest_tests.yml` - PHP unit/feature tests with MySQL service

**Timeout recommendation**: 20-30 minutes

### Heavy Workflows (Large 4-core Runner)
Use `ubuntu-latest-4-core` for:
- End-to-end browser tests (Playwright, Selenium, Cypress)
- Complex build processes with multiple compilation steps
- Parallel matrix builds with high concurrency
- Resource-intensive test suites
- Workflows with heavy CPU/memory usage

**Example workflows in this repository**:
- `admin_playwright_tests.yml` - Browser-based E2E tests (6 shards)
- `shop_playwright_tests.yml` - Browser-based E2E tests (6 shards)

**Timeout recommendation**: 45-60 minutes

### Extra Heavy Workflows (Extra Large 8-core Runner)
Use `ubuntu-latest-8-core` for:
- Large-scale parallel builds (>10 matrix jobs)
- Performance testing suites
- Load testing workflows
- Machine learning model training
- Very large monorepo builds

**Timeout recommendation**: 60-120 minutes

## Best Practices

### 1. Always Set Timeout Minutes
Every job should have a `timeout-minutes` setting to prevent runaway processes:

```yaml
jobs:
  my_job:
    runs-on: ubuntu-latest
    timeout-minutes: 30  # Prevent runaway processes
```

### 2. Use Concurrency Controls
Prevent redundant workflow runs with concurrency settings:

```yaml
concurrency:
  group: ${{ github.workflow }}-${{ github.ref }}
  cancel-in-progress: true
```

This automatically cancels in-progress runs when new commits are pushed to the same branch.

### 3. Implement Resource Monitoring
Add resource monitoring steps to track actual usage:

```yaml
- name: Resource Monitoring - Before Tests
  run: |
    echo "=== Resource Usage ==="
    echo "CPU Cores: $(nproc)"
    free -h
    df -h
    echo "Start Time: $(date -u +%Y-%m-%dT%H:%M:%SZ)"
```

### 4. Optimize Matrix Strategies
- Use `fail-fast: false` for test sharding to get complete results
- Limit parallel jobs to reasonable numbers (typically 6-10 max)
- Consider sequential execution for very light tasks

### 5. Cache Dependencies
Always cache dependencies to reduce build times:

```yaml
- name: Cache Composer Dependencies
  uses: actions/cache@v3
  with:
    path: vendor
    key: ${{ runner.os }}-composer-${{ hashFiles('**/composer.lock') }}
```

### 6. Shard Large Test Suites
For browser tests or large test suites, use sharding:

```yaml
strategy:
  matrix:
    shard-index: [1, 2, 3, 4, 5, 6]
    shard-total: [6]
```

## Audit and Review Process

### When to Review Runner Selection
1. **After workflow completion time changes significantly** (>20% variation)
2. **When adding new workflows** - categorize and assign appropriate runners
3. **Monthly review** - Check actual resource usage vs. allocated resources
4. **After failures due to timeouts or resource limits**

### How to Audit Resource Usage
1. Check workflow run logs for resource monitoring output
2. Review job duration trends in GitHub Actions insights
3. Monitor runner queue times and concurrency limits
4. Compare costs vs. performance improvements

### Optimization Checklist
- [ ] All workflows have `timeout-minutes` configured
- [ ] Concurrency controls implemented where appropriate
- [ ] Resource monitoring steps added to key workflows
- [ ] Runner sizes match actual resource requirements
- [ ] Dependencies are cached effectively
- [ ] Matrix strategies are optimized for parallelization
- [ ] Unused or redundant workflows are identified and removed

## Runner Selection Decision Tree

```
START
  |
  ├─> Runs browser tests (Playwright/Selenium)?
  |   └─> YES -> Use ubuntu-latest-4-core (timeout: 60min)
  |
  ├─> Requires database service + extensive tests?
  |   └─> YES -> Use ubuntu-latest (timeout: 30min)
  |
  ├─> Simple validation (lint/style/translation)?
  |   └─> YES -> Use ubuntu-latest (timeout: 10min)
  |
  └─> Complex parallel builds (>10 jobs)?
      └─> YES -> Use ubuntu-latest-8-core (timeout: 90min)
```

## Cost Optimization Tips

1. **Reduce redundant runs**: Use `concurrency` to cancel outdated workflows
2. **Right-size runners**: Don't use 4-core runners for simple tasks
3. **Optimize test execution**: Faster tests = lower costs
4. **Cache aggressively**: Reduce setup time in every run
5. **Use appropriate timeouts**: Don't waste runner time on stuck jobs
6. **Review retention policies**: Reduce artifact retention periods where possible

## Current Repository Configuration

| Workflow | Runner Type | Timeout | Justification |
|----------|------------|---------|---------------|
| Admin Playwright Tests | ubuntu-latest-4-core | 60min | CPU/memory intensive browser tests with 6 shards |
| Shop Playwright Tests | ubuntu-latest-4-core | 60min | CPU/memory intensive browser tests with 6 shards |
| Pest Tests | ubuntu-latest | 30min | Unit/feature tests with MySQL, moderate complexity |
| Pint Tests | ubuntu-latest | 10min | Lightweight PHP code style checking |
| Translation Tests | ubuntu-latest | 10min | Simple validation script |

## Support and Questions

For questions about runner selection or to request changes to workflow configurations, please:
1. Review this documentation
2. Check actual resource usage in workflow logs
3. Create an issue with resource usage data to support your request
4. Tag it with `github-actions` and `optimization`

## References

- [GitHub Actions Runner Specifications](https://docs.github.com/en/actions/using-github-hosted-runners/about-github-hosted-runners)
- [Workflow Syntax Reference](https://docs.github.com/en/actions/reference/workflow-syntax-for-github-actions)
- [Best Practices for GitHub Actions](https://docs.github.com/en/actions/security-guides/security-hardening-for-github-actions)
