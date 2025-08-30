terraform {
  required_providers {
    digitalocean = {
      source  = "digitalocean/digitalocean"
      version = "~> 2.20"
    }
  }
}

provider "digitalocean" {
  token = var.do_token
}

provider "cloudflare" {
  api_token = var.cloudflare_api_token
}

resource "digitalocean_ssh_key" "deployer" {
  name       = "xui-deployer"
  public_key = var.ssh_public_key
}

resource "digitalocean_volume" "xui_data" {
  region      = var.region
  size        = 100
  name        = "xui-data-${random_id.suffix.hex}"
}

resource "random_id" "suffix" { byte_length = 4 }

resource "digitalocean_droplet" "xui" {
  name   = "xui-baseline"
  region = var.region
  size   = var.size
  image  = var.image
  ssh_keys = [digitalocean_ssh_key.deployer.fingerprint]

  user_data = <<-EOF
    #cloud-config
    runcmd:
  - [ bash, -lc, "echo '${var.deploy_bundle_base64}' | base64 -d > /tmp/deploy.tar.gz && mkdir -p /opt/deploy && tar -xzf /tmp/deploy.tar.gz -C /opt/deploy && chmod +x /opt/deploy/deploy/baseline-deploy-ubuntu22.sh && export XUI_DOMAIN='${var.xui_location}.${var.xui_domain}' && export CERTBOT_EMAIL='${var.certbot_email}' && if [ -n '${var.cloudflare_origin_cert_b64}' ]; then echo '${var.cloudflare_origin_cert_b64}' | base64 -d > /tmp/cf_origin.pem && export CLOUDFLARE_ORIGIN_CERT="$(cat /tmp/cf_origin.pem | sed "s/\$/\\n/g")"; fi && if [ -n '${var.cloudflare_origin_key_b64}' ]; then echo '${var.cloudflare_origin_key_b64}' | base64 -d > /tmp/cf_origin.key && export CLOUDFLARE_ORIGIN_KEY="$(cat /tmp/cf_origin.key | sed "s/\$/\\n/g")"; fi && /opt/deploy/deploy/baseline-deploy-ubuntu22.sh ${var.deployment_tier} ${var.install_3xui_flag} '${var.admin_ips}' " ]
  EOF

  depends_on = [digitalocean_volume.xui_data]
}

resource "digitalocean_volume_attachment" "attach" {
  droplet_id = digitalocean_droplet.xui.id
  volume_id  = digitalocean_volume.xui_data.id
}

output "ipv4" { value = digitalocean_droplet.xui.ipv4_address }

resource "cloudflare_record" "xui_a" {
  zone_id = var.cloudflare_zone_id
  name    = "${var.xui_location}.${var.xui_domain}"
  value   = digitalocean_droplet.xui.ipv4_address
  type    = "A"
  ttl     = 300
}
