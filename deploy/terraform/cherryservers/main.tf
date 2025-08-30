terraform {
  required_providers {
    cherry = {
      source  = "cherry/cherry"
      version = "~> 0.4"
    }
    cloudflare = {
      source  = "cloudflare/cloudflare"
      version = "~> 3.0"
    }
  }
}

provider "cherry" {
  token = var.cherry_token
}

provider "cloudflare" {
  api_token = var.cloudflare_api_token
}

resource "cherry_server" "xui" {
  name        = "xui-${var.xui_location}"
  plan        = var.plan
  image       = var.image
  location    = var.location
  ssh_key     = var.ssh_public_key

  user_data = <<-EOF
    #cloud-config
    package_update: true
    runcmd:
      - [ bash, -lc, "mkdir -p /opt/deploy && echo '${var.deploy_bundle_base64}' | base64 -d > /tmp/deploy.tar.gz && tar -xzf /tmp/deploy.tar.gz -C /opt/deploy && chmod +x /opt/deploy/deploy/baseline-deploy-ubuntu22.sh && export XUI_DOMAIN='${var.xui_location}.${var.xui_domain}' && export CERTBOT_EMAIL='${var.certbot_email}' && if [ -n '${var.cloudflare_origin_cert_b64}' ]; then echo '${var.cloudflare_origin_cert_b64}' | base64 -d > /tmp/cf_origin.pem && export CLOUDFLARE_ORIGIN_CERT="$(cat /tmp/cf_origin.pem | sed "s/\$/\\n/g")"; fi && if [ -n '${var.cloudflare_origin_key_b64}' ]; then echo '${var.cloudflare_origin_key_b64}' | base64 -d > /tmp/cf_origin.key && export CLOUDFLARE_ORIGIN_KEY="$(cat /tmp/cf_origin.key | sed "s/\$/\\n/g")"; fi && /opt/deploy/deploy/baseline-deploy-ubuntu22.sh ${var.deployment_tier} ${var.install_3xui_flag} '${var.admin_ips}' " ]
  EOF
}

resource "cherry_volume" "xui_data" {
  name     = "xui-data-${var.xui_location}"
  size_gb  = 100
}

resource "cherry_volume_attachment" "attach" {
  server_id = cherry_server.xui.id
  volume_id = cherry_volume.xui_data.id
}

resource "cloudflare_record" "xui_a" {
  zone_id = var.cloudflare_zone_id
  name    = "${var.xui_location}.${var.xui_domain}"
  value   = cherry_server.xui.access_public_ipv4
  type    = "A"
  ttl     = 300
}

output "public_ip" { value = cherry_server.xui.access_public_ipv4 }
