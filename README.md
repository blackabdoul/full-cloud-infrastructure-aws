# Full Cloud Infrastructure System

> **Course:** Practical in Cloud Computing — Mr Ajayi  
> **Student:** Abdoul Malick LAWAL  
> **Status:** ✅ Complete & decommissioned (AWS Free Tier — May 2026)

A production-grade, eight-layer cloud infrastructure deployed entirely on AWS Free Tier from scratch — no CloudFormation, no Elastic Beanstalk, no shortcuts. Every resource configured manually to demonstrate real understanding of each service.

---

## Live Demo (archived)

| Endpoint | Status |
|---|---|
| `https://dlwztrz85p46c.cloudfront.net` | Decommissioned — May 2026 |
| `http://cloud-project-front-mike.s3-website-us-east-1.amazonaws.com` | Decommissioned |

Screenshots of the live system are in [`/evidence`](./evidence/).

---

## Architecture

```
User Browser
    │
    ▼ HTTPS
┌─────────────────────┐
│   CloudFront CDN    │  dlwztrz85p46c.cloudfront.net
│  (edge caching,     │  Miss → Hit demonstrated in DevTools
│   HTTP→HTTPS)       │
└────────┬────────────┘
         │
         ▼ origin request
┌─────────────────────┐
│   S3 Static Site    │  cloud-project-front-mike
│   index.html        │  Static hosting, versioning enabled
│   fetch() → EC2     │
└────────┬────────────┘
         │ fetch() API call
         ▼
┌─────────────────────┐     ┌──────────────────────────┐
│  EC2 — api.php      │────▶│  AWS Secrets Manager     │
│  Apache + PHP 8.5   │     │  rds/cloud-project/      │
│  AWS SDK            │     │  credentials             │
│  (IAM role, no keys)│◀────│  (username + password)   │
└────────┬────────────┘     └──────────────────────────┘
         │ PDO (MySQL)
         ▼
┌─────────────────────┐
│  RDS MySQL 8.4      │  Private subnet — no public IP
│  cloud-project-db   │  Accessible only via ec2-sg → rds-sg
│  appdb.products     │
└─────────────────────┘
         │
         ▼ JSON
    Browser renders product table
```

**Zero hardcoded credentials at any layer.**

---

## Infrastructure — 8 Phases

| Phase | Service | Key Configuration |
|---|---|---|
| 01 | **IAM** | `admin-user` + MFA, `ec2-project-role` (3 scoped policies), $10 budget alert |
| 02 | **VPC** | `10.0.0.0/16`, 4 subnets × 2 AZs, IGW, NAT Gateway, route tables |
| 03 | **Security Groups** | `ec2-sg` (80/443/22), `rds-sg` (3306 sourced from `ec2-sg` — SG chaining) |
| 04 | **RDS** | MySQL 8.4, `db.t4g.micro`, private subnet, encrypted, credentials in Secrets Manager |
| 05 | **EC2** | `t2.micro`, Amazon Linux 2023, Apache + PHP 8.5.5, AWS SDK, `api.php` |
| 06 | **S3** | Static website hosting, versioning, public-read bucket policy, CORS |
| 07 | **CloudFront** | HTTPS, edge caching (Miss→Hit verified), permanent URL, CORS locked to CF domain |
| 08 | **CloudWatch** | Dashboard (4 widgets), CPU alarm + SNS, CloudTrail audit, Apache log streaming |

---

## Security Design

| Layer | Control |
|---|---|
| Identity | MFA on root + admin. EC2 uses IAM role — no access keys in code |
| Network | RDS in private subnet, no public IP or DNS |
| Firewall | `rds-sg` sources from `ec2-sg` ID (not IP range) — SG chaining |
| Credentials | Secrets Manager retrieval at runtime via IAM role. Zero `.env` files |
| Transport | CloudFront enforces HTTPS. HTTP auto-redirected |
| Data at rest | RDS encrypted (KMS). S3 SSE-S3 |
| Audit | CloudTrail multi-region. `GetSecretValue` events confirm IAM auth chain |

---

## Real Challenges Solved

**1. Secrets Manager before RDS existed**  
The console wizard required an existing RDS instance. Created the secret manually as JSON first, linked to RDS after provisioning.

**2. MySQL `ERROR 1045` — Access Denied**  
Caused by a space between `-p` and the password in the CLI (`-p password` vs `-ppassword`). Classic.

**3. HTTP 500 on `api.php`**  
PHP syntax error — missing closing single quote in the PDO connection string. Caught via error logs.

**4. SELinux blocking Apache → RDS**  
Amazon Linux 2023 runs SELinux in enforcing mode. `httpd` cannot make outbound TCP connections by default.  
Fix: `setsebool -P httpd_can_network_connect 1`

**5. Ephemeral EC2 public IP**  
EC2 public IPs change on restart. The hardcoded IP in `index.html` broke the frontend after any stop/start.  
Fix: CloudFront domain is permanent. `fetch()` updated to use `dlwztrz85p46c.cloudfront.net`.

**6. CloudFront deletion blocked by pricing plan**  
`You can't delete this distribution while it's subscribed to a pricing plan.`  
Fix: Disable distribution → cancel plan → delete at end of billing cycle.

---

## Repository Structure

```
/
├── README.md
├── evidence/              # Screenshots from live AWS console
│   ├── phase-01-iam/
│   ├── phase-02-vpc/
│   ├── phase-03-sg/
│   ├── phase-04-rds/
│   ├── phase-05-ec2/
│   ├── phase-06-s3/
│   ├── phase-07-cloudfront/
│   └── phase-08-cloudwatch/
├── src/
│   ├── api.php            # EC2 backend — Secrets Manager + PDO + JSON
│   └── index.html         # S3 frontend — static site + fetch() call
├── config/
│   ├── bucket-policy.json # S3 public-read bucket policy
│   └── iam-policy-ec2-s3-scoped.json  # Custom scoped EC2→S3 policy
└── docs/
    ├── Project_Report.docx
    └── Presentation_Guide.docx
```

---

## AWS Resource Reference

| Resource | Identifier |
|---|---|
| Account | `468060799649` (Eyran_cloud) |
| EC2 Instance | `i-01164a1cf9f64ee32` |
| RDS Endpoint | `cloud-project-db.cy7iu4ws6euf.us-east-1.rds.amazonaws.com` *(deleted)* |
| CloudFront Domain | `dlwztrz85p46c.cloudfront.net` *(disabled)* |
| Secrets Manager | `rds/cloud-project/credentials` *(deleted)* |
| Region | `us-east-1` (N. Virginia) |

---

## Decommissioning Notes

All resources were deleted or disabled on completion to avoid post-Free Tier charges (Free Tier expired May 31 2026). Deletion order: RDS → EC2 → Secrets Manager → CloudFront → CloudWatch logs → CloudTrail trail → NAT Gateway. VPC, IAM, S3 bucket, and Security Groups retained at zero cost.

---

## Deliverables

- [x] 47-slide PowerPoint presentation (real screenshots, live data)
- [x] 6-page Presentation Guide (Word)
- [x] 8 LinkedIn posts — one per phase
- [x] Project Report (Word — formal submission)
- [x] This README

---

*Built May 2026 — Practical in Cloud Computing, Undergraduate Year 3*
