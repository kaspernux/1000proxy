variable "do_token" {
  type      = string
  sensitive = true
}

variable "ssh_public_key" {
  type = string
}

variable "region" {
  type    = string
  default = "ams3"
}

variable "size" {
  type    = string
  default = "s-4vcpu-8gb"
}

variable "image" {
  type    = string
  default = "ubuntu-22-04-x64"
}

variable "deploy_bundle_base64" {
  type      = string
  sensitive = true
}

variable "deployment_tier" { type = string, default = "baseline" }
variable "install_3xui_flag" { type = string, default = "" }
variable "admin_ips" { type = string, default = "" }
variable "x3ui_install_url" { type = string, default = "https://raw.githubusercontent.com/MHSanaei/3x-ui/master/install.sh" }

variable "cloudflare_api_token" { type = string, sensitive = true, default = "" }
variable "cloudflare_zone_id" { type = string, default = "" }
variable "xui_location" { type = string, default = "location" }
variable "xui_domain" { type = string, default = "1000proxy.me" }
variable "certbot_email" { type = string, default = "" }
variable "cloudflare_origin_cert_b64" { type = string, sensitive = true, default = "" }
variable "cloudflare_origin_key_b64" { type = string, sensitive = true, default = "" }
