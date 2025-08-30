# Cherry Servers Terraform example

This example provisions a server on Cherry Servers, attaches a volume and runs the repo deploy bundle via cloud-init. It also creates a Cloudflare DNS A record for `{xui_location}.{xui_domain}`.

Steps

1. Create the deploy bundle:

```bash
./deploy/pack-deploy.sh deploy-bundle.b64
```

2. Create `terraform.tfvars` in this folder with values similar to the Hetzner example. Include `deploy_bundle_base64 = file("../deploy-bundle.b64")` and Cloudflare variables.

3. Run Terraform:

```bash
terraform init
terraform apply -auto-approve
```

4. Terraform outputs the server public IP. Visit `https://{xui_location}.{xui_domain}/proxy/` after DNS propagation.
