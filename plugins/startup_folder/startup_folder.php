<?php

/**
 * Startup folder
 *
 * Per-user preference (Settings > Preferences > Mailbox View) choosing the folder that
 * opens after login and whenever the mail task is opened without an explicit folder
 * (e.g. the «E-Mail» button in the task menu). Default: INBOX, or the admin's
 * $config['startup_folder_default']. A folder that no longer exists falls back to INBOX.
 * Requests that name a folder (_mbox) are left alone.
 *
 * @license GNU GPLv3+
 */
class startup_folder extends rcube_plugin
{
    public $task = '.*';

    /** @var rcmail */
    private $rc;

    public function init()
    {
        $this->rc = rcmail::get_instance();

        $this->load_config('config.inc.php.dist');
        $this->load_config();

        $this->add_hook('startup', [$this, 'startup']);

        if ($this->rc->task == 'settings') {
            $this->add_hook('preferences_list', [$this, 'preferences_list']);
            $this->add_hook('preferences_save', [$this, 'preferences_save']);
        }

        // The task menu's «E-Mail» button always appends _mbox=INBOX (app.js switch_task);
        // drop it so the server-side startup folder applies. Logged-in HTML pages only.
        if (!empty($this->rc->user->ID) && $this->rc->output->type == 'html' && empty($_REQUEST['_framed'])) {
            $this->include_script('startup_folder.js');
        }
    }

    /**
     * Folder to open: user preference > admin default > INBOX
     */
    private function configured_folder()
    {
        $folder = (string) $this->rc->config->get('startup_folder', '');

        if ($folder === '') {
            $folder = (string) $this->rc->config->get('startup_folder_default', 'INBOX');
        }

        return $folder !== '' ? $folder : 'INBOX';
    }

    /**
     * Mail task opened without _mbox (full page load): switch to the startup folder
     */
    public function startup($args)
    {
        if ($args['task'] != 'mail' || !empty($args['action']) || empty($this->rc->user->ID)
            || $this->rc->output->ajax_call
            || isset($_GET['_mbox']) || isset($_POST['_mbox'])
            || isset($_GET['_uid']) || isset($_GET['_search'])
        ) {
            return $args;
        }

        $folder = $this->configured_folder();

        if ($folder !== 'INBOX') {
            $storage = $this->rc->get_storage();
            if (!$storage->folder_exists($folder)) {
                $folder = 'INBOX';   // deleted/renamed: silent fallback
            }
        }

        $_GET['_mbox'] = $folder;
        $_SESSION['mbox'] = $folder;
        $_SESSION['page'] = 1;

        return $args;
    }

    public function preferences_list($args)
    {
        if ($args['section'] != 'mailbox') {
            return $args;
        }

        $dont_override = (array) $this->rc->config->get('dont_override', []);
        if (in_array('startup_folder', $dont_override)) {
            return $args;
        }

        $this->add_texts('localization/');

        // make sure we have a connection (the section is rendered without one in some setups)
        $this->rc->storage_connect();

        $select = $this->rc->folder_selector([
            'name' => '_startup_folder',
            'id' => 'rcmfd_startup_folder',
            'folder_filter' => 'mail',
            'maxlength' => 60,
        ]);

        $current = $this->configured_folder();
        if ($current !== 'INBOX' && !$this->rc->get_storage()->folder_exists($current)) {
            $current = 'INBOX';
        }

        $args['blocks']['main']['options']['startup_folder'] = [
            'title' => html::label('rcmfd_startup_folder', rcube::Q($this->gettext('startupfolder'))),
            'content' => $select->show($current),
        ];

        return $args;
    }

    public function preferences_save($args)
    {
        if ($args['section'] != 'mailbox') {
            return $args;
        }

        $dont_override = (array) $this->rc->config->get('dont_override', []);
        if (in_array('startup_folder', $dont_override)) {
            return $args;
        }

        $folder = rcube_utils::get_input_string('_startup_folder', rcube_utils::INPUT_POST, true);
        if ($folder !== null && $folder !== '') {
            $args['prefs']['startup_folder'] = $folder;
        }

        return $args;
    }
}
