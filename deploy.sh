#!/bin/bash

# TW4 Golf Management System - Deployment Script
# Author: Ned Bollard
# Description: Automated deployment script for solo development

echo "🚀 Starting TW4 Deployment Process..."

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

# Check if we're in the right directory
if [ ! -f "docker-compose.yml" ]; then
    print_error "docker-compose.yml not found. Please run from project root."
    exit 1
fi

# Stage all changes
print_status "Staging all changes..."
git add .

# Get commit message
if [ -z "$1" ]; then
    echo -e "${YELLOW}Enter commit message:${NC}"
    read -p "Commit: " commit_message
else
    commit_message="$1"
fi

# Commit changes
print_status "Committing with message: $commit_message"
git commit -m "$commit_message"

# Push to GitHub
print_status "Pushing to GitHub..."
git push origin master

# Check result
if [ $? -eq 0 ]; then
    print_status "✅ Deployment completed successfully!"
    echo -e "${GREEN}Your changes are now live on GitHub.${NC}"
else
    print_error "❌ Deployment failed!"
    exit 1
fi

echo "🎯 Deployment process completed."
