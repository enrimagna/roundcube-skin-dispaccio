# startup_folder: Roundcube plugin

Lets each user choose which folder opens when they log in or open the mail task
(Roundcube **1.7.x**, tested on 1.7.4 with the *Il Dispaccio* skin and stock *Elastic*).

* **Settings > Preferences > Mailbox View** (IT: *Impaginazione messaggi*) gets a new option,
  **Startup folder** / **Cartella all'apertura**. It lists the user's folders: Inbox, Drafts,
  Sent, Junk, Trash and any custom folders.
* The default is **Inbox** (or the admin default, see below). Users who leave it alone see
  stock Roundcube behaviour.
* The chosen folder opens:
  * right after login
  * whenever the mail task is opened without an explicit folder (`?_task=mail`), e.g. by clicking
    **E-Mail** in the left rail from Contacts or Settings, or the Dispaccio wordmark
* Links that name a folder (`?_task=mail&_mbox=…`, message links with `_uid`, searches) work as before.
* If the saved folder has been deleted or renamed, Inbox opens. No error is shown, and the
  preference shows Inbox again.
* No external requests. Just one small JS file and the localization strings.

## Install

1. Copy the `startup_folder` folder into Roundcube's `plugins/` directory
   (or unzip `startup_folder.zip` there). The result should be `plugins/startup_folder/startup_folder.php`.
2. Enable it in `config/config.inc.php`:
   ```php
   $config['plugins'] = ['…', 'startup_folder'];
   ```
   With the official Docker image you can add `startup_folder` to `ROUNDCUBEMAIL_PLUGINS`, or
   append `$config['plugins'][] = 'startup_folder';` to a `/var/roundcube/config/*.php` file.
   That file is read on every request, so no restart is needed if it was already present
   when the container started.
3. Optional: `cp plugins/startup_folder/config.inc.php.dist plugins/startup_folder/config.inc.php`
   and set the default for users who haven't chosen a folder:
   ```php
   $config['startup_folder_default'] = 'INBOX';   // IMAP name, e.g. 'Progetti' or 'INBOX/Clienti'
   ```
   You can also set it in Roundcube's main config.
4. Optional: to impose the folder on everybody and hide the option, add `'startup_folder'` to
   `$config['dont_override']`.

Nothing needs to be installed in the database. The choice is stored in the user's preferences
(`startup_folder`).

## Notes

* The list comes from Roundcube's own folder selector, so it shows **subscribed** folders.
  Folders are subscribed by default when created in Roundcube.
* Inside the mail task, Roundcube core greys out the rail's E-Mail button, because it's the current task.
  That behaviour is unchanged. From inside mail, use the folder list (or the Dispaccio wordmark).
* The rail button is handled in `startup_folder.js`. Stock Roundcube would hard-code `_mbox=INBOX` there.
* Hooks used: `startup` (choose the folder), `preferences_list` / `preferences_save` (the option),
  plus `include_script` for the rail.
* Localization: `en_US`, `it_IT`. Other languages fall back to English.

License: GPL-3.0-or-later.
