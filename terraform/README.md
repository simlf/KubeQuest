# Azure VM Management with Terraform

## Introduction

This repository contains Terraform configuration for deploying and managing Azure virtual machines and related infrastructure. [Terraform](https://www.terraform.io/) is an Infrastructure as Code (IaC) tool that allows you to define and provision infrastructure using a declarative configuration language. With Terraform, you can version, reuse, and share your infrastructure configurations.

This project specifically sets up:
- Virtual networks and subnets in Azure
- Network interfaces and public IPs
- Linux virtual machines with proper configurations
- All necessary supporting resources

## How Terraform Works

Terraform uses a declarative approach where you specify the desired end state of your infrastructure. The key workflow is:

1. Write configuration files that describe your infrastructure
2. Run `terraform plan` to see what changes will be made
3. Run `terraform apply` to create or update your infrastructure
4. Use `terraform destroy` when you want to tear down resources

## Prerequisites

- [Terraform](https://www.terraform.io/downloads.html) 1.0+ installed
- Azure CLI installed and configured (`az login`)
- Azure subscription with appropriate permissions
- Azure subscription ID

## Setup

1. **Clone this repository**:

   ```bash
   git clone <repository-url>
   cd <repository-directory>
   ```

2. **Configure Environment Variables**:

   Set your Azure subscription ID as an environment variable:

   ```bash
   export TF_VAR_subscription_id=your-azure-subscription-id
   ```

   This keeps sensitive information out of your codebase.

3. **Initialize Terraform**:

   ```bash
   terraform init
   ```

   This downloads the necessary providers and sets up the backend.

4. **Review the Planned Changes**:

   ```bash
   terraform plan
   ```

   This shows what resources will be created, modified, or destroyed.

5. **Apply Configuration**:

   ```bash
   terraform apply
   ```

   Type 'yes' when prompted to create the resources.

## Accessing Secrets from HashiCorp Vault Secrets (HVS)

You can retrieve your Azure subscription ID and other secrets from HashiCorp Vault Secrets using multiple methods:

### Using the HCP CLI

1. **Install the CLI**:

   Homebrew is a package manager for macOS.
   ```bash
   brew tap hashicorp/tap
   brew install hashicorp/tap/hcp
   ```
   More installation options are available on the HashiCorp website.

2. **Setup**:

   a. Login to the HashiCorp Cloud Platform to access HVS.
   ```bash
   hcp auth login
   ```

   b. Once successfully logged in run:
   ```bash
   hcp profile init --vault-secrets
   ```

   c. Now set your default config by selecting Organization, Project, and App.

3. **Read your secret**:

   ```bash
   hcp vault-secrets secrets open {desired secret}
   ```

   You may also inject secrets into your app as environment variables by passing a command as string, as shown below for an app using python.
   ```bash
   hcp vault-secrets run -- python3 my_app.py
   ```

### Using the Web UI

You can also log into the HashiCorp Cloud Platform web interface to access and manage your secrets through a user-friendly dashboard.

### Using the API

For programmatic access, HCP Vault Secrets provides a REST API that can be integrated into scripts or automation workflows. Consult the HashiCorp developer documentation for details.

## Project Structure

- `main.tf` - Contains the main resource definitions for Azure VMs and networking
- `variables.tf` - Defines input variables used in the configuration
- `backend.tf` - Configures Terraform state storage
- `terraform.tfvars.example` - Example variable definitions (do not commit actual values)

## Security Best Practices

- Never commit sensitive credentials or `terraform.tfvars` to version control
- Use environment variables for secrets in CI/CD pipelines
- Store state files securely when using remote backends
- Review access permissions to your Azure resources regularly

## Alternative Variable Management

You can also use a local `terraform.tfvars` file for development:

## Troubleshooting

If you encounter issues:
- Ensure Azure CLI is properly authenticated
- Verify your subscription has sufficient permissions
- Check that environment variables are correctly set
- Review Terraform logs for detailed error information
