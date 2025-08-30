variable "hcloud_token" {
  description = "Hetzner Cloud API token"
  type        = string
  sensitive   = true
}

variable "ssh_public_key" {
  description = "Public SSH key content to inject into the server"
  type        = string
}

variable "server_name" {
  description = "Name for the Hetzner server"
  type        = string
  default     = "xui-baseline"
}

variable "server_type" {
  description = "Hetzner server type"
  type        = string
  default     = "cx31" # 4 vCPU / 8 GB baseline
}

variable "image" {
  description = "Server image"
  type        = string
  default     = "ubuntu-22.04"
}

variable "location" {
  description = "Hetzner location (nbg, hel, fsn)"
  type        = string
  default     = "nbg"
}

variable "deployment_tier" {
  description = "Deployment tier to pass to the provision script: baseline|recommended|high"
  type        = string
  default     = "baseline"
}

variable "install_3xui_flag" {
  description = "Flag passed to the installer to trigger 3x-ui install; set to \"--install-3xui\" to enable"
  type        = string
  default     = ""
}

variable "deploy_bundle_base64" {
  description = "Base64-encoded tar.gz of the deploy/ folder to extract on the server"
  type        = string
  sensitive   = true
}

variable "admin_ips" {
  description = "Optional comma-separated admin IPs to restrict UI access"
  type        = string
  default     = ""
}

variable "x3ui_install_url" {
  description = "Optional install script URL for 3x-ui (overrides default)"
  type        = string
  default     = "https://raw.githubusercontent.com/MHSanaei/3x-ui/master/install.sh"
}

variable "cloudflare_api_token" {
  description = "Cloudflare API token with DNS edit permissions"
  type        = string
  sensitive   = true
  default     = ""
}

variable "cloudflare_zone_id" {
  description = "Cloudflare zone ID for 1000proxy.me"
  type        = string
  default     = ""
}

variable "xui_location" {
  description = "Location label used to form subdomain (e.g. 'us-east')"
  type        = string
  default     = "location"
}

variable "xui_domain" {
  description = "Base domain for XUI servers (e.g. 1000proxy.me)"
  type        = string
  default     = "1000proxy.me"
}

variable "certbot_email" {
  description = "Email used for Let's Encrypt registration"
  type        = string
  default     = ""
}

variable "cloudflare_origin_cert_b64" {
  description = "Base64 encoded Cloudflare Origin certificate PEM (use file("./cf_origin_cert.b64"))"
  type        = string
  sensitive   = true
  default     = ""
}

variable "cloudflare_origin_key_b64" {
  description = "Base64 encoded Cloudflare Origin private key PEM (use file("./cf_origin_key.b64"))"
  type        = string
  sensitive   = true
  default     = ""
}
