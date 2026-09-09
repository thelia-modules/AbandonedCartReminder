# Abandoned Carts Reminder

Detects carts left behind, mails up to three reminders carrying a signed link back to the cart,
and reports what those reminders brought back.

Requires **Thelia 3.0** or later. The 2.x line lives on the `thelia2` branch and is not maintained
here.

## Install

```bash
composer require thelia/abandoned-cart-reminder-module
```

Then activate the module in the back office, or:

```bash
bin/console module:activate AbandonedCartReminder
```

Activation creates its two tables and seeds the three e-mail templates and the default settings.
Re-activating never overwrites templates a merchant has edited.

## Set the shop URL first

The reminders are sent from the command line, where the only source of an absolute URL is the
shop's own setting. **Without it every recovery link points at `http://localhost` and no customer
can follow one.** Set `url_site` in *Configuration > Configuration parameters*.

The command refuses to send while it is empty, and says so.

## Schedule the command

```bash
0 * * * * /path/to/php /path/to/thelia/bin/console remind:abandoned-carts >> /path/to/thelia/var/log/abandoned-carts.log 2>&1
```

Hourly is enough: the delays are configured in hours, and nothing is lost by checking less often
than the shortest one. The command is safe to run concurrently with a shop under load — it never
runs inside a visitor's request and sends in batches.

| Option | |
|---|---|
| `--dry-run` | Reports what would be sent, without sending or recording anything |
| `--limit N` | Caps how many reminders this run may send. Counts reminders sent, not carts examined |

## Configure

*Modules > Abandoned Carts Reminder* carries the settings, what the reminders brought back, and
the list of followed carts with a search and sortable columns.

| Setting | Default | |
|---|---|---|
| First reminder delay | 4 h | Hours of inactivity before a cart is reminded |
| Second reminder delay | 24 h | Hours after the first reminder |
| Third reminder delay | 72 h | Hours after the second reminder |
| Recovery link lifetime | 7 days | Minimum 60 seconds |
| Reminders per run | 200 | Caps one command run |

A delay **left empty** disables that reminder and stops the chain there, so a shop that wants a
single reminder empties the second one. A delay of **zero** means due at once — the two are not the
same thing.

Keep the chain shorter than `purification_cart_no_order_days` (60 days by default), or the
maintenance purge deletes carts before their last reminder. The command warns when it does not.

## Who gets reminded

A cart is reminded when it holds at least one item, has not changed since the first delay, has no
order, and carries a usable address — either the account it belongs to, or an address the visitor
left on the cart page.

A customer stops it in one click from any reminder. The refusal holds for their future carts too,
and anonymizing an account erases it along with the rest of that address's history.

## The recovery link

The link is signed with a key derived from the application secret, carries its own expiry, and is
accepted once. The signature covers the customer's current password hash, so changing a password
retires every link issued before it. Following a link opens a **restricted session**: it cannot
change a password or delete the account, and it is never opened for a cart with no account behind
it.

Every refusal answers the same way — expired, tampered with, already used, unknown or rate limited
all land on the shop front — so the answer never says which links name something real. The route is
capped per cart and per client.

## What the recovered figure covers

A cart deleted by the maintenance purge leaves the list with its history, so the total covers the
retention window of carts without an order rather than all time. The screen says so.
