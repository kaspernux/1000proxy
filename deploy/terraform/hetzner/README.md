# Terraform: Hetzner baseline server

This minimal Terraform example provisions a single Hetzner Cloud server and runs the repository's deploy script via cloud-init.

Prerequisites
- Terraform 1.5+
- Hetzner Cloud API token

Usage

1. Create a `terraform.tfvars` with these values:

```hcl
hcloud_token = "YOUR_HCLOUD_TOKEN"
ssh_public_key = file("~/.ssh/id_rsa.pub")
server_name = "xui-baseline"
# tier: baseline | recommended | high
deployment_tier = "baseline"
# set to --install-3xui to run the upstream 3x-ui installer
install_3xui_flag = "--install-3xui"
```

2. Run:

```bash
terraform init
terraform apply -auto-approve
```

3. Terraform outputs the server IP. SSH in with the private key to verify logs.

Notes
- This is minimal; for production you'd add volumes, backups, floating IPs, and DNS.

Pack and provide the deploy bundle

Run the helper from the repo root to create a base64 bundle file:

```bash
./deploy/pack-deploy.sh deploy-bundle.b64
```

Then set `deploy_bundle_base64 = file("./deploy-bundle.b64")` in your `terraform.tfvars`.

Optional variables
- `admin_ips` — comma-separated list of admin IPs to restrict UI access (default empty)
- `x3ui_install_url` — override URL used to install 3x-ui (default upstream master raw URL)

Cloudflare and domain naming
- Set `cloudflare_api_token` and `cloudflare_zone_id` in your `terraform.tfvars` to allow Terraform to create DNS records.
- Set `xui_location` to the short location/country name (for example `us`, `nl`, `jp`) and `xui_domain` to `1000proxy.me`. Terraform will create an A record for `{xui_location}.{xui_domain}`.

To enable automatic TLS via Certbot, set `certbot_email` in `terraform.tfvars`. The deploy script will render the nginx template and request a certificate for `{xui_location}.{xui_domain}`.

Cloudflare Origin CA (optional)
If you prefer Cloudflare Origin certificates, create base64-encoded files of your origin cert and key:

```bash
base64 cf_origin.pem > cf_origin_cert.b64
base64 cf_origin.key > cf_origin_key.b64
```

Then set in `terraform.tfvars`:

```hcl
cloudflare_origin_cert_b64 = file("./cf_origin_cert.b64")
cloudflare_origin_key_b64  = file("./cf_origin_key.b64")
```

Terraform will decode these and pass them to cloud-init; the deploy script writes them into `/etc/ssl/cf_origin.pem` and `/etc/ssl/cf_origin.key` for nginx to use.

