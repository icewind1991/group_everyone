# group_everyone

Adds a virtual "Everyone" group to a Nextcloud instance. 

## Usage

Simply enable the app and the group will be created.

## Excluding guest accounts

By default every account is a member of the group, including the accounts created by the [Guests](https://github.com/nextcloud/guests) app.
To keep guest accounts out of the group, run:

```bash
occ group_everyone:exclude-guests --on
```

Use `--off` to include them again, or run the command without an option to show the current setting.
The change applies immediately: excluded guests lose access to anything shared with the "Everyone" group, and included guests get access to it.
