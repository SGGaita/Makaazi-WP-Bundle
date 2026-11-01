# Roles and capabilities

## Property capabilities

`property` uses its own capability type, so access to listings is separate from blog posts:

`edit_properties`, `edit_others_properties`, `edit_published_properties`, `edit_private_properties`, `publish_properties`, `read_private_properties`, `delete_properties`, `delete_others_properties`, `delete_published_properties`, `delete_private_properties`.

With `map_meta_cap`, WordPress maps `edit_post` / `delete_post` on a property to these. Taxonomy terms are assigned with `edit_properties`.

**Administrator** and **Editor** get all of them (`Makaazi_Roles::install()`).

## Roles

| Role | Slug | Capabilities | Where they work |
|---|---|---|---|
| Agent | `mk_agent` | `read`, `upload_files`, `edit_properties`, `edit_published_properties`, `publish_properties`, `delete_properties`, `delete_published_properties` | wp-admin (own listings only) and the front-end dashboard |
| Property owner | `mk_owner` | `read` | Front-end dashboard only; redirected away from wp-admin; no admin bar |

Roles are (re)installed when `Makaazi_Roles::VER` is higher than the `makaazi_roles_ver` option. Bump `VER` when you change role capabilities.

Owners do not have `edit_properties`. Their listings are created and saved by `Makaazi_Submission` after its own checks (`can_manage()`: the user must be the author, or the property's agent).

## Feature switches change capabilities at runtime

`Makaazi_Roles::dynamic_caps()` filters `user_has_cap` for agents:

- **Agents off** (`agents_enabled = 0`): every property capability and `upload_files` are removed, so agents cannot use wp-admin or the dashboard form. Their profiles and the agent box disappear from the front end. Nothing is deleted; switching back on restores everything.
- **Review agent listings** (`agent_review = 1`): `publish_properties` is removed, so new agent listings are saved as Pending.

Owners are controlled in `Makaazi_Accounts::can_list()`: they can list only while `owners_enabled` is on and their account is not pending approval.

## Other rules

- `keep_owners_out()` (`admin_init`): users without `edit_posts` or `edit_properties` who are owners or agents are redirected to the dashboard page. AJAX and REST requests are allowed through.
- `own_properties_only()` (`pre_get_posts`): in wp-admin, users without `edit_others_properties` only see their own properties.
- Email verification: owners who sign up with `owner_email_verify` on get `_mk_verify`; `authenticate` refuses them until they click the link (or an admin uses **Activate** on Users).

## Checking capabilities in your code

```php
if ( current_user_can( 'edit_post', $property_id ) ) { … }        // edit this listing
if ( Makaazi_Accounts::can_list() ) { … }                        // may use Add property
if ( Makaazi_Roles::is_agent() || Makaazi_Roles::is_owner() ) { … }
if ( makaazi_agents_on() ) { … }                                 // Agents switch
if ( makaazi_owners_on() ) { … }                                 // Owner submissions switch
```
