# DNS Info

Adds a DNS section to the debug information and some DNS checks to the Health Check feature.

## What does DNS Info do?

DNS Info adds a new section to the Site Health Info tab. This debug information helps to see those DNS settings, which are not very visible, but could be the reason for mail deliverability problems.

Besides those debug information, two status checks are added. One for the existence of an SPF record and one for the existence of a DMARC record. There are no checks about the record itself or if they are correct. They just need to be there.

If you need a starting point. This is a basic DMARC record, which does nothing, but now it is set and can be extended.
`v=DMARC1; p=none;`

This is the same for SPF. It allows the MX server and the server behind the A record to send mails. If another sends, we are recommending to do nothing (no spam or bouncing). 
`v=spf1 mx a ?all`

Both record should be extended according to your setup.

Please read more about SPF, DMARC and DKIM and ask your hoster, if you have problems setting this up correctly.

## Installation

At the moment you need to download the ZIP file here from GitHub or use something like [Git Installer](https://www.git-installer.com/) or [Git Updater](https://git-updater.com/)

## Changelog

### 1.0.0

* Initial release.
