Baseline XUI deploy (Ubuntu 22.04)

What this bundle contains
- `baseline-deploy-ubuntu22.sh` — root script to install Docker, UFW, certbot, fail2ban and apply sysctl tuning
- `docker-compose.xui.yml` — example docker-compose for XUI (edit ports/image as needed)
- `xui.service.template` — systemd unit template to run the compose stack from `/opt/xui`

Quick start & full runbook

This README shows how to package the deploy bundle, populate provider variables, and run Terraform to provision an XUI server with DNS, TLS, backups and optional monitoring.

Prerequisites
- A local clone of this repository.
- Terraform 1.5+ installed.
- Provider account(s) and API credentials (Hetzner / DigitalOcean / AWS).
- Cloudflare API token with DNS edit permissions and your `cloudflare_zone_id` for `1000proxy.me`.
- An SSH public key to inject into the server(s).

1) Package the deploy bundle

Create a base64-encoded tarball of the `deploy/` folder which Terraform embeds into cloud-init:

```bash
./deploy/pack-deploy.sh deploy-bundle.b64
```

Place `deploy-bundle.b64` in the same folder where you run Terraform (or use an absolute path in `terraform.tfvars`).

2) (Optional) Prepare Cloudflare Origin CA certs

If you use Cloudflare in front and prefer Origin CA certs (recommended when proxying via Cloudflare), base64-encode them locally:

```bash
base64 cf_origin.pem > cf_origin_cert.b64
base64 cf_origin.key > cf_origin_key.b64
```

3) Create provider-specific `terraform.tfvars`

Example `deploy/terraform/hetzner/terraform.tfvars`:

```hcl
# Hetzner example
hcloud_token               = "YOUR_HCLOUD_TOKEN"
ssh_public_key             = file("~/.ssh/id_rsa.pub")
server_name                = "xui-us"
server_type                = "cx31"
image                      = "ubuntu-22.04"
location                   = "nbg"
deployment_tier            = "baseline"           # baseline|recommended|high
install_3xui_flag          = "--install-3xui"     # set to "" to skip
deploy_bundle_base64       = file("./deploy-bundle.b64")
admin_ips                  = "203.0.113.5,198.51.100.10" # comma-separated admin IPs
xui_location               = "us"                 # forms us.1000proxy.me
xui_domain                 = "1000proxy.me"
certbot_email              = "admin@example.com"
cloudflare_api_token       = "YOUR_CLOUDFLARE_API_TOKEN"
cloudflare_zone_id         = "YOUR_CLOUDFLARE_ZONE_ID"
cloudflare_origin_cert_b64 = file("./cf_origin_cert.b64") # optional
cloudflare_origin_key_b64  = file("./cf_origin_key.b64")  # optional
```

Notes
- Use the provider folder matching where you will run Terraform (`deploy/terraform/hetzner`, `.../digitalocean`, `.../aws`).
- Keep `terraform.tfvars` local and protect it (it contains secrets).

4) (Optional) Provide a pinned 3x-ui release (recommended)

To avoid running upstream install scripts unpinned, add a release tarball to the deploy bundle:

- Place `deploy/releases/3x-ui.tar.gz` in your local `deploy/` directory before running `pack-deploy.sh`. The tarball should contain an `install.sh` the deploy script can run.
- Or set `x3ui_install_url` + `x3ui_release_sha256` in `terraform.tfvars` to download a specific release and verify checksum.

5) Run Terraform

```bash
cd deploy/terraform/hetzner
terraform init
terraform apply -auto-approve
```

Terraform will:
- Create a server and attached data volume.
- Attach the volume and extract the deploy bundle via cloud-init.
- Create a Cloudflare DNS A record for `{xui_location}.{xui_domain}`.
- Optionally pass Cloudflare Origin certs and other envs to the instance.

6) Post-deploy verification

SSH into the server (use your private key for `ssh_public_key`):

```bash
ssh root@<server-ip>
```

Quick checks:

```bash
# docker containers (xui container)
sudo docker ps -a

# nginx and certificate
sudo systemctl status nginx
sudo nginx -t
sudo certbot certificates  # if you used certbot

# x-ui service (if installed via the baked installer)
sudo systemctl status x-ui || sudo docker logs xui

# backups
ls -la /var/backups/xui

# monitoring
sudo systemctl status netdata
```

Verify HTTP(S) and UI path from your workstation:

```bash
curl -vI https://us.1000proxy.me/proxy/
```

7) Backups & encryption

The deploy script installs `/usr/local/bin/backup-xui.sh` and a cron job at 02:30 UTC daily. Configure uploads and encryption on the server (or in `terraform.tfvars` via cloud-init envs):

- `BACKUP_GPG_RECIPIENT` (GPG public key identifier) or `BACKUP_GPG_PASSPHRASE` (symmetric encryption)
- `BACKUP_S3_BUCKET` (s3://bucket or s3-compatible endpoint). Ensure `aws` CLI is configured on the server or add s3 client credentials.

8) Monitoring

Set `INSTALL_MONITORING=netdata` in the environment passed to the deploy script via `terraform.tfvars` (or run `./deploy/baseline-deploy-ubuntu22.sh netdata` locally) to install Netdata. I can add Prometheus/node_exporter instead if you prefer.

9) Cloudflare & TLS choices

- Let’s Encrypt (Certbot): the deploy script will run `certbot --nginx` if `certbot_email` is provided. Use this if you want public certs directly on the origin.
- Cloudflare Origin CA: recommended when you proxy through Cloudflare. Create origin certs and pass them via `cloudflare_origin_cert_b64` / `cloudflare_origin_key_b64` in `terraform.tfvars`. Terraform decodes and cloud-init writes them into `/etc/ssl/cf_origin.pem` and `/etc/ssl/cf_origin.key` for nginx to use.

10) DNS & Cloudflare proxying

- Terraform creates an A record for `{xui_location}.{xui_domain}`. In the Cloudflare dashboard you can toggle the orange cloud (proxy) for that record. If proxied, Cloudflare will present their public certs and your origin only needs an Origin CA cert.

11) Multi-location / bulk provisioning

For multiple locations, repeat Terraform runs per `xui_location` (e.g., `us`, `nl`, `jp`) — automate with a small script that sets `xui_location`, updates `terraform.tfvars` and runs `terraform apply` in a loop. I can provide that script if you want.

12) Security notes

- Never paste secrets (API tokens, private keys) into chat. Keep `terraform.tfvars` private.
- Use SSH key auth and restrict SSH to admin IPs using provider firewalls and/or UFW.
- Prefer Cloudflare Origin CA when using Cloudflare proxy; set Cloudflare to "Full (strict)" between Cloudflare and origin.

13) Troubleshooting & limits

- If cloud-init fails: check `/var/log/cloud-init-output.log` and `journalctl -u cloud-init` on the server.
- If the bundle is too large for provider user_data limits, upload the bundle to a private object store and modify cloud-init to download it (I can change the Terraform to do this for you).

Next steps I can implement for you
- Add provider-level firewall rules (allow SSH only from admin IPs; HTTP/HTTPS only from Cloudflare IPs).
- Provide a bulk provisioning script to create many location servers and produce a table of domain→IP mappings.
- Add a CI job to build and pin `3x-ui.tar.gz`, publish it to a private object store and update Terraform to fetch it securely.

If you want any of the next steps automated, tell me which one and I will add it to the repo.
