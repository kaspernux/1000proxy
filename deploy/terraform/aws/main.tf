provider "aws" { region = var.aws_region }

provider "cloudflare" {
  api_token = var.cloudflare_api_token
}

resource "aws_instance" "xui" {
  ami           = var.ami
  instance_type = var.instance_type
  key_name      = var.ssh_key_name

  user_data = <<-EOF
    #cloud-config
    runcmd:
  - [ bash, -lc, "echo '${var.deploy_bundle_base64}' | base64 -d > /tmp/deploy.tar.gz && mkdir -p /opt/deploy && tar -xzf /tmp/deploy.tar.gz -C /opt/deploy && chmod +x /opt/deploy/deploy/baseline-deploy-ubuntu22.sh && export XUI_DOMAIN='${var.xui_location}.${var.xui_domain}' && export CERTBOT_EMAIL='${var.certbot_email}' && if [ -n '${var.cloudflare_origin_cert_b64}' ]; then echo '${var.cloudflare_origin_cert_b64}' | base64 -d > /tmp/cf_origin.pem && export CLOUDFLARE_ORIGIN_CERT="$(cat /tmp/cf_origin.pem | sed "s/\$/\\n/g")"; fi && if [ -n '${var.cloudflare_origin_key_b64}' ]; then echo '${var.cloudflare_origin_key_b64}' | base64 -d > /tmp/cf_origin.key && export CLOUDFLARE_ORIGIN_KEY="$(cat /tmp/cf_origin.key | sed "s/\$/\\n/g")"; fi && /opt/deploy/deploy/baseline-deploy-ubuntu22.sh ${var.deployment_tier} ${var.install_3xui_flag} '${var.admin_ips}' " ]
  EOF

  tags = { Name = "xui-baseline" }
}

resource "aws_ebs_volume" "xui_data" {
  availability_zone = aws_instance.xui.availability_zone
  size              = 100
  tags = { Name = "xui-data" }
}

resource "aws_volume_attachment" "attach" {
  device_name = "/dev/sdf"
  instance_id = aws_instance.xui.id
  volume_id   = aws_ebs_volume.xui_data.id
}

output "public_ip" { value = aws_instance.xui.public_ip }

resource "cloudflare_record" "xui_a" {
  zone_id = var.cloudflare_zone_id
  name    = "${var.xui_location}.${var.xui_domain}"
  value   = aws_instance.xui.public_ip
  type    = "A"
  ttl     = 300
}
