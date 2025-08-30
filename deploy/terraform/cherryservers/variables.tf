variable "cherry_token" {
  description = "Cherry Servers API token"
  type        = string
  sensitive   = true
}

variable "ssh_public_key" {
  description = "SSH public key content to inject into the server"
  type        = string
}

variable "plan" {
  description = "Cherry Servers plan (instance size)"
  type        = string
  default     = "c2-4x8"
}

variable "image" {
  description = "OS image slug"
  type        = string
  default     = "ubuntu-22-04"
}

variable "location" {
  description = "Cherry Servers location slug"
  type        = string
  default     = "fra"
}

variable "deploy_bundle_base64" {
  description = "Base64-encoded tar.gz of the deploy/ folder"
  type        = string
  sensitive   = true
}

variable "deployment_tier" {
  description = "Deployment tier: baseline|recommended|high"
  type        = string
  default     = "baseline"
}

variable "install_3xui_flag" {
  description = "set to --install-3xui to run 3x-ui installer"
  type        = string
  default     = ""
}

variable "admin_ips" {
  description = "Comma-separated admin IPs to restrict UI access"
  type        = string
  default     = ""
}

variable "xui_location" { type = string, default = "location" }
variable "xui_domain"   { type = string, default = "1000proxy.me" }
variable "certbot_email" { type = string, default = "" }

variable "cloudflare_api_token" { type = string, sensitive = true, default = "" }
variable "cloudflare_zone_id"  { type = string, default = "" }

variable "cloudflare_origin_cert_b64" { type = string, sensitive = true, default = "" }
variable "cloudflare_origin_key_b64"  { type = string, sensitive = true, default = "" }
