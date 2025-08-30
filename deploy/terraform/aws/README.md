AWS Terraform example

1. Generate deploy bundle:

```bash
./deploy/pack-deploy.sh deploy-bundle.b64
```

2. Create `terraform.tfvars` with AWS provider settings and set `deploy_bundle_base64 = file("./deploy-bundle.b64")`.

3. Run `terraform init` and `terraform apply -auto-approve`.
