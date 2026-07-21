<?php

/**
 * @file
 * Seed data for ticket management demo users and tickets.
 *
 * Loaded by the ticket:seed Drush command (see database/setup-notes.md).
 */

return [
  'users' => [
    [
      'name' => 'alex.morgan',
      'mail' => 'alex.morgan@example.com',
      'password' => 'TicketSeed1!',
    ],
    [
      'name' => 'jamie.lee',
      'mail' => 'jamie.lee@example.com',
      'password' => 'TicketSeed1!',
    ],
    [
      'name' => 'sam.patel',
      'mail' => 'sam.patel@example.com',
      'password' => 'TicketSeed1!',
    ],
    [
      'name' => 'riley.chen',
      'mail' => 'riley.chen@example.com',
      'password' => 'TicketSeed1!',
    ],
  ],
  'tickets' => [
    [
      'title' => 'Login page returns 500 after deploy',
      'description' => 'Since last night\'s deployment, staff cannot sign in. The login form submits and the browser shows a generic 500 error. Apache error log shows a PHP fatal in the user module stack trace.',
      'priority' => 'high',
      'status' => 'open',
      'assignee' => 'alex.morgan',
      'reporter' => 'jamie.lee',
    ],
    [
      'title' => 'VPN access for contractor starting Monday',
      'description' => 'A design contractor joins on Monday and needs VPN credentials plus access to the staging wiki. No assignee yet — needs an admin to assign someone before status can be moved by staff.',
      'priority' => 'medium',
      'status' => 'open',
      'assignee' => NULL,
      'reporter' => 'sam.patel',
    ],
    [
      'title' => 'Update broken FAQ link on support portal',
      'description' => 'The “Reset your password” FAQ entry points to a retired URL and returns 404. Please update the link to the current knowledge-base article.',
      'priority' => 'low',
      'status' => 'open',
      'assignee' => 'jamie.lee',
      'reporter' => 'riley.chen',
    ],
    [
      'title' => 'SSO redirect loop for Azure AD users',
      'description' => 'Users authenticating via Azure AD are bounced between /user/login and /saml/acs indefinitely. Local accounts are unaffected. Started after the IdP certificate rollover.',
      'priority' => 'high',
      'status' => 'open',
      'assignee' => 'sam.patel',
      'reporter' => 'alex.morgan',
    ],
    [
      'title' => 'Checkout times out on card payments',
      'description' => 'Customers report the spinner hangs for 60 seconds and then fails when paying by Visa or Mastercard. PayPal still works. Reproduced in staging with test cards.',
      'priority' => 'high',
      'status' => 'in_progress',
      'assignee' => 'alex.morgan',
      'reporter' => 'jamie.lee',
    ],
    [
      'title' => 'Duplicate charges appearing on invoices',
      'description' => 'Finance flagged three accounts billed twice for the same subscription period. Need to confirm whether this is a cron replay issue or a webhook double-delivery problem.',
      'priority' => 'medium',
      'status' => 'in_progress',
      'assignee' => 'sam.patel',
      'reporter' => 'riley.chen',
    ],
    [
      'title' => 'Email notifications delayed by two hours',
      'description' => 'Ticket comment notifications and password-reset emails are arriving roughly two hours late. Queue workers appear healthy; suspect upstream SMTP throttling. Fields are edit-locked while resolved.',
      'priority' => 'medium',
      'status' => 'resolved',
      'assignee' => 'riley.chen',
      'reporter' => 'alex.morgan',
    ],
    [
      'title' => 'Password reset link expired immediately',
      'description' => 'Users clicking the reset link within one minute see “link expired.” Issue was traced to server clock skew; fix verified and ticket closed.',
      'priority' => 'low',
      'status' => 'closed',
      'assignee' => 'jamie.lee',
      'reporter' => 'sam.patel',
    ],
  ],
];
