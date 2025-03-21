variable "location" {
  description = "Azure region to deploy resources"
  default     = "spaincentral"
}

variable "resource_group_name" {
  description = "Name of the resource group"
  default     = "rg-group-015"
}

variable "vm_size" {
  description = "Size of the VMs"
  default     = "Standard_B2ls_v2"
}

variable "hcp_project_id" {
  description = "HCP project ID"
}
