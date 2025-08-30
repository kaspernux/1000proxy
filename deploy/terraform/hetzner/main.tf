terraform {
  required_providers {
    hcloud = {
      source  = "hetznercloud/hcloud"
      version = "~> 1.40"
    }
  }
}

provider "hcloud" {
  token = var.hcloud_token
}

provider "cloudflare" {
  api_token = var.cloudflare_api_token
}

resource "hcloud_ssh_key" "deployer" {
  name       = "xui-deployer-${var.server_name}"
  public_key = var.ssh_public_key
}

data "http" "repo_raw" {
  url = "https://raw.githubusercontent.com/kaspernux/1000proxy/main/deploy/baseline-deploy-ubuntu22.sh"
}

resource "hcloud_volume" "xui_data" {
  name      = "${var.server_name}-data"
  size      = 100
  location  = var.location
  format    = "ext4"
  labels = {
    purpose = "xui-data"
  }
}

resource "hcloud_server" "xui" {
  name        = var.server_name
  server_type = var.server_type
  image       = var.image
  location    = var.location
  ssh_keys    = [hcloud_ssh_key.deployer.id]

  user_data = <<-EOF
    #cloud-config
    package_update: true
    runcmd:
      - [ bash, -lc, "mkdir -p /opt/deploy && echo '${var.deploy_bundle_base64}' | base64 -d > /tmp/deploy.tar.gz && tar -xzf /tmp/deploy.tar.gz -C /opt/deploy && chmod +x /opt/deploy/deploy/baseline-deploy-ubuntu22.sh && export XUI_DOMAIN='${var.xui_location}.${var.xui_domain}' && export CERTBOT_EMAIL='${var.certbot_email}' && if [ -n '${var.cloudflare_origin_cert_b64}' ]; then echo '${var.cloudflare_origin_cert_b64}' | base64 -d > /tmp/cf_origin.pem && export CLOUDFLARE_ORIGIN_CERT="$(cat /tmp/cf_origin.pem | sed "s/\$/\\n/g")"; fi && if [ -n '${var.cloudflare_origin_key_b64}' ]; then echo '${var.cloudflare_origin_key_b64}' | base64 -d > /tmp/cf_origin.key && export CLOUDFLARE_ORIGIN_KEY="$(cat /tmp/cf_origin.key | sed "s/\$/\\n/g")"; fi && /opt/deploy/deploy/baseline-deploy-ubuntu22.sh ${var.deployment_tier} ${var.install_3xui_flag} '${var.admin_ips}' " ]
  EOF

  depends_on = [hcloud_volume.xui_data]
}

resource "hcloud_volume_attachment" "attach_data" {
  server_id = hcloud_server.xui.id
  volume_id = hcloud_volume.xui_data.id
  automount = true
}

resource "hcloud_volume_snapshot" "daily" {
  depends_on = [hcloud_volume.xui_data]
  volume_id  = hcloud_volume.xui_data.id
  description = "daily-snapshot-${var.server_name}"
}

resource "cloudflare_record" "xui_a" {
  zone_id = var.cloudflare_zone_id
  name    = "${var.xui_location}.${var.xui_domain}"
  value   = hcloud_server.xui.ipv4_address
  type    = "A"
  ttl     = 300
}

output "ipv4" {
  value = hcloud_server.xui.ipv4_address
}
