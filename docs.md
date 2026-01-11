# 📚 Trico AI Assistant - Full Documentation

Hey, this is me laying out everything about Trico AI Assistant, the WordPress plugin that lets you create awesome websites with just one prompt. I put this doc together so you can install, configure, and use it without any hassle. Writing it in a casual vibe like we're chatting, but keeping it complete and easy to follow. I'll toss in a light joke here and there that's relatable anywhere, like how this tool is open-source under Apache 2.0 so anyone can use it freely, no strings attached – unlike some subscriptions that sneak in extra fees, haha. Just making it fun to read so you don't bail halfway.

---

## 📋 Table of Contents

1. [Overview](#overview)
2. [Requirements](#requirements)
3. [Installation](#installation)
4. [Configuration](#configuration)
5. [Usage Guide](#usage-guide)
6. [API Reference](#api-reference)
7. [Troubleshooting](#troubleshooting)
8. [FAQ](#faq)

---

## Overview

**Trico AI Assistant** is an AI-powered plugin for WordPress that generates modern, eye-catching websites from a single prompt using Groq AI, pulls images from Pollinations.ai, and deploys directly to Cloudflare Pages. Super handy for quick site builds.

### Key Features

| Feature | Description |
|---------|-------------|
| 🤖 AI Generation | Create full websites with Groq AI (Llama models) |
| 🖼️ AI Images | Unlimited free image generation via Pollinations.ai |
| 🎨 Modern Design | Glassmorphism, Neubrutalism, Bento Grid styles |
| 📦 Static Export | Export to pure HTML/CSS/JS |
| 🚀 CF Pages Deploy | One-click deploy to Cloudflare Pages |
| 📊 Synalytics | Cloudflare Web Analytics dashboard |
| 🔄 API Rotation | Supports up to 15 API keys for teams |
| 💾 B2 Storage | Backblaze B2 integration for media |
| 🌐 Whitelabel | Full custom domain support |

These features make it easy for anyone to get started, especially small businesses looking to go online without breaking the bank.

---

## Requirements

### Minimum Requirements

- **WordPress**: 6.0 or higher
- **PHP**: 7.4 or higher
- **MySQL/MariaDB**: 5.7+ or TiDB
- **Memory**: 256MB (512MB recommended)

### Required API Keys

| Service | Purpose | How to Get |
|---------|---------|------------|
| **Groq API** | AI text/code generation | [console.groq.com](https://console.groq.com) |
| **Cloudflare** | Deployment & analytics | [dash.cloudflare.com](https://dash.cloudflare.com) |
| **Backblaze B2** | Media storage (optional) | [backblaze.com/b2](https://www.backblaze.com/b2) |

### Cloudflare API Token Permissions

When creating your Cloudflare API token, include these permissions:

```
Account → Cloudflare Pages → Edit
Account → Account Analytics → Read  
Zone → Zone → Read (for custom domains)
```

Make sure to grant just these – don't go full access or I might remote into your account... just kidding, keep it secure!

---

## Installation

### Option A: Hugging Face Spaces (Recommended for Synavy)

1. **Create HF Space** with Docker SDK

2. **Add Dockerfile**:
```dockerfile
FROM wordpress:latest

# ... (see Dockerfile in repository)

RUN git clone --depth 1 https://github.com/sadidft/trico-plugin-wordpress.git /tmp/trico && \
    cp -r /tmp/trico/trico-theme /usr/src/wordpress/wp-content/themes/ && \
    cp -r /tmp/trico/trico-ai-assistant /usr/src/wordpress/wp-content/plugins/ && \
    cp -r /tmp/trico/mu-plugins/* /usr/src/wordpress/wp-content/mu-plugins/
```

3. **Set HF Secrets** (see Configuration section)

4. **Factory Rebuild**

### Option B: Standard WordPress

1. Download or clone this repository
2. Upload `trico-theme` to `wp-content/themes/`
3. Upload `trico-ai-assistant` to `wp-content/plugins/`
4. Upload `mu-plugins/synavy-cookie-fix.php` to `wp-content/mu-plugins/`
5. Activate theme and plugin

### Option C: Docker Standalone

```bash
# Clone repository
git clone https://github.com/sadidft/trico-plugin-wordpress.git
cd trico-plugin-wordpress

# Build and run
docker build -f Dockerfile-demo -t trico-wordpress .
docker run -d -p 8080:80 \
  -e GROQ_KEY_1=your_groq_key \
  -e CF_API_TOKEN=your_cf_token \
  -e CF_ACCOUNT_ID=your_account_id \
  -e TRICO_DOMAIN=your-domain.com \
  trico-wordpress
```

Installation's straightforward, no need for a tech degree – unlike setting up some old-school servers that feel like rocket science.

---

## Configuration

### Environment Variables / Secrets

#### Required

| Variable | Description | Example |
|----------|-------------|---------|
| `GROQ_KEY_1` | First Groq API key | `gsk_abc123...` |
| `CF_API_TOKEN` | Cloudflare API token | `v1.0-abc123...` |
| `CF_ACCOUNT_ID` | Cloudflare account ID | `abc123def456...` |
| `TRICO_DOMAIN` | Default domain for deployments | `synpages.synavy.com` |

#### Optional - Additional Groq Keys

```
GROQ_KEY_2=gsk_...
GROQ_KEY_3=gsk_...
GROQ_KEY_4=gsk_...
GROQ_KEY_5=gsk_...
... up to GROQ_KEY_15
```

#### Optional - B2 Storage

| Variable | Description |
|----------|-------------|
| `B2_KEY_ID` | B2 application key ID |
| `B2_APP_KEY` | B2 application key |
| `B2_BUCKET_ID` | B2 bucket ID |
| `B2_BUCKET_NAME` | B2 bucket name |

#### For HF Spaces - Database

| Variable | Description |
|----------|-------------|
| `WORDPRESS_DB_HOST` | Database host |
| `WORDPRESS_DB_USER` | Database username |
| `WORDPRESS_DB_PASSWORD` | Database password |
| `WORDPRESS_DB_NAME` | Database name |
| `WP_DOMAIN` | WordPress domain |

Get these set up right, and you're good to go – no surprises like hidden fees in your phone bill.

---

## Usage Guide

### Step 1: Access Trico Dashboard

After activation, go to: **Admin → Trico AI → Dashboard**

You'll see:
- Total projects count
- API keys status
- Recent projects
- Quick actions

### Step 2: Generate Your First Website

1. Go to **Trico AI → Generate**

2. Enter your prompt. Be descriptive:
   ```
   Buatkan landing page untuk toko roti modern bernama "Roti Masseh".
   Gunakan warna warm (coklat, cream). Style glassmorphism.
   Include: hero section dengan gambar roti, features (fresh, delivery, 
   affordable), testimonial, dan CTA WhatsApp.
   ```

3. Select options:
   - **CSS Framework**: Tailwind (recommended), Bootstrap, or Vanilla
   - **Language**: Indonesian or English

4. Click **Generate & Save**

5. Wait 30-60 seconds for AI generation

### Step 3: Edit in WordPress

After generation:
1. Click **Edit in WordPress** to open Block Editor
2. Modify content, images, colors as needed
3. Native WordPress editing - drag, drop, change text

### Step 4: Deploy to Cloudflare Pages

1. Go to **Trico AI → Projects**
2. Find your project
3. Click **🚀 Deploy**
4. Wait for deployment (1-2 minutes)
5. Your site is live at `projectname.pages.dev`

### Step 5: Custom Domain (Optional)

1. Go to project's **Deploy Settings**
2. Enter your subdomain or custom domain
3. Add CNAME record to your DNS:
   ```
   CNAME  yoursite  →  projectname.pages.dev
   ```

This makes your site look pro, like upgrading from a basic phone to a smartphone.

---

## API Reference

### AI Models Used

| Model | Purpose | Speed |
|-------|---------|-------|
| `llama-3.3-70b-versatile` | Full page generation | Slower, better quality |
| `llama-3.1-70b-versatile` | Section updates | Medium |
| `llama-3.1-8b-instant` | SEO, quick edits | Fast |

### API Rotation

Trico rotates through available API keys automatically:
- Request 1 → Key 1
- Request 2 → Key 2
- Request 3 → Key 3
- ...cycles back to Key 1

If a key hits rate limit:
1. Automatically skips to next key
2. Retries the request
3. Marks key as limited (auto-reset after timeout)

### Database Tables

Trico creates these tables (TiDB-compatible, no foreign keys):

```sql
{prefix}trico_projects   -- Project data
{prefix}trico_history    -- Generation history (max 4 per project)
{prefix}trico_b2_files   -- B2 file tracking
```

---

## Troubleshooting

### Common Issues

#### "No API keys configured"
**Solution**: Add `GROQ_KEY_1` to your environment/secrets

#### "Failed to connect to database"
**Solution**: Check database credentials and TiDB compatibility

#### "Cloudflare deployment failed"
**Solution**: 
1. Verify `CF_API_TOKEN` has correct permissions
2. Check `CF_ACCOUNT_ID` is correct
3. Ensure project name is valid (lowercase, alphanumeric, hyphens)

#### "Images not loading"
**Solution**: 
- Pollinations.ai URLs are direct - check if site allows external images
- For B2: verify B2 credentials are correct

#### Tables not created
**Solution**: 
1. Deactivate plugin
2. Delete any `trico_*` tables manually if exist
3. Reactivate plugin

### Debug Mode

Add to wp-config.php for debugging:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

Check `/wp-content/debug.log` for Trico errors.

Bugs happen, but fixing them is easier than untangling earbuds.

---

## FAQ

**Q: Is this free to use?**
A: The plugin is free (Apache 2.0). You need your own API keys - Groq has free tier, Cloudflare Pages is free, Pollinations.ai is free unlimited.

**Q: How many websites can I generate?**
A: Unlimited. Only limited by your Groq API quota.

**Q: Can I use my own domain?**
A: Yes! Set up CNAME record pointing to your Cloudflare Pages project.

**Q: Does it work with standard WordPress hosting?**
A: Yes, but deployment features require Cloudflare API access.

**Q: Can I edit generated websites?**
A: Yes! Generated content is native WordPress blocks - fully editable.

**Q: What happens if all API keys are rate limited?**
A: System queues requests and retries when keys become available.

---

## Support

- **GitHub Issues**: [github.com/sadidft/trico-plugin-wordpress/issues](https://github.com/sadidft/trico-plugin-wordpress/issues)
- **Documentation**: This file
- **License**: Apache 2.0

---

## Credits

Built with ❤️ by Synavy Team

Powered by:
- [Groq](https://groq.com) - AI inference
- [Pollinations.ai](https://pollinations.ai) - AI image generation
- [Cloudflare Pages](https://pages.cloudflare.com) - Static hosting
- [WordPress](https://wordpress.org) - CMS platform
