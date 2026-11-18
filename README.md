# Wolvy video filter for Moodle (`filter_wolvy`)

Put a protected [Wolvy](https://wolvy.net) video anywhere Moodle shows text (a Page, a Label, a quiz question, a forum post) with one tag:

```
[wolvy id="a1b2c3d4e5f60718293a"]
```

Each player carries a viewer token signed on your Moodle server for the student watching. The video's watermark shows that student, and Wolvy records the viewing session against them. A pasted `<iframe>` cannot do this: its address is the same for everyone. Moodle also strips iframes from text written by users who are not trusted, while a tag survives.

For an activity that is marked complete when the student has watched enough, see the Wolvy video activity (`mod_wolvy`), which uses this filter's settings.

## Requirements

- Moodle 4.5 (LTS) to 5.3 (LTS).
- A Wolvy account: your account ID and, for per-student watermarks, the viewer signing secret, both from the Wolvy dashboard.

## Install

1. Copy this folder to `filter/wolvy` in your Moodle code (on Moodle 5.1 and later, `public/filter/wolvy`), or install the ZIP under *Site administration > Plugins > Install plugins*.
2. Visit *Site administration > Notifications* to finish the installation.
3. Turn the filter on under *Site administration > Plugins > Filters > Manage filters*.
4. Open *Site administration > Plugins > Filters > Wolvy video* and enter your account ID and signing secret.

## The tag

| Attribute | Required | Example | Meaning |
|---|---|---|---|
| `id` | yes | `id="a1b2c3d4e5f60718293a"` | The video's ID in Wolvy |
| `aspect` | no | `aspect="4:3"` | Width to height; the default is set in the filter settings |
| `title` | no | `title="Lesson 1"` | Accessible name of the player |

Straight, single, typographic and HTML-encoded quotes all work, so the tag survives whatever your text editor does to it. A tag alone in a paragraph replaces the paragraph.

## Settings

| Setting | Default | Notes |
|---|---|---|
| Wolvy account ID | — | Required. Nothing is shown until it is set; administrators see a hint |
| Viewer signing secret | — | 64 characters. Without it players carry no token, so the watermark cannot name the student |
| Viewer ID template | `moodle-{id}` | `{id}`, `{username}`, `{email}`, `{idnumber}`. Cut to 64 bytes. Falls back to `moodle-{id}` when it comes out empty |
| Token lifetime | 2 hours | Must outlast the longest video a student watches in one sitting. Wolvy accepts up to 24 hours |
| Visitors who are not logged in | A message asking them to log in | Or the player without a token, or nothing |
| Default shape | 16:9 | |

The settings page also shows a status list: whether this server reproduces Wolvy's published test token, whether the account ID and secret are set, and the site domain to allow in Wolvy if your account restricts domains.

To keep the secret out of Moodle's database, set it in `config.php`:

```php
$CFG->forced_plugin_settings['filter_wolvy']['signingsecret'] = '…';
```

## Privacy

The plugin stores nothing in Moodle. The viewer ID (by default the Moodle user ID) is sent to Wolvy inside the signed token in each player's address. Wolvy shows it on the watermark and records it with each viewing session. This is declared through Moodle's Privacy API as data sent to an external location.

## Tests

```
vendor/bin/phpunit --testsuite filter_wolvy_testsuite
```

`tests/token_test.php` checks the signer against Wolvy's published test vector (docs.wolvy.net/viewer-tokens/).

## Licence

GNU GPL v3 or later. See `LICENSE`.
