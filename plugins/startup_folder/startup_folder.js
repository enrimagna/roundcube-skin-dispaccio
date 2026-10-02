/**
 * Startup folder plugin: the task menu's «E-Mail» button normally goes to ?_task=mail&_mbox=INBOX.
 * Open ?_task=mail without _mbox instead, so the server opens the user's startup folder.
 *
 * @license GNU GPLv3+
 */
window.rcmail && rcmail.addEventListener('init', function () {
    var switch_task = rcmail.switch_task;

    rcmail.switch_task = function (task) {
        if (task === 'mail') {
            return this.redirect(this.get_task_url('mail'));
        }

        return switch_task.apply(this, arguments);
    };
});
