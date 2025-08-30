variable "aws_region" { type = string, default = "eu-central-1" }
variable "instance_type" { type = string, default = "t3a.medium" }
variable "ami" { type = string, default = "ami-0a4a70bd98c6d6441" }
variable "ssh_key_name" { type = string }
variable "deploy_bundle_base64" { type = string, sensitive = true }
variable "deployment_tier" { type = string, default = "baseline" }
variable "install_3xui_flag" { type = string, default = "" }
variable "admin_ips" { type = string, default = "" }
variable "x3ui_install_url" { type = string, default = "https://raw.githubusercontent.com/MHSanaei/3x-ui/master/install.sh" }

variable "cloudflare_api_token" { type = string, sensitive = true, default = "" }
variable "cloudflare_zone_id" { type = string, default = "" }
variable "xui_location" { type = string, default = "location" }
variable "xui_domain" { type = string, default = "1000proxy.me" }
variable "cloudflare_origin_cert_b64" { type = string, sensitive = true, default = "" }
variable "cloudflare_origin_key_b64" { type = string, sensitive = true, default = "" }
