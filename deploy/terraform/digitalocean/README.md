DigitalOcean Terraform example

1. Generate deploy bundle:

```bash
./deploy/pack-deploy.sh deploy-bundle.b64
```

2. Create `terraform.tfvars` with your `do_token`, `ssh_public_key`, and set `deploy_bundle_base64 = file("./deploy-bundle.b64")`.

3. Run `terraform init` and `terraform apply -auto-approve`.
