# Glossary — technical term → plain-language analogy

Starting points, not scripts. Adapt to whatever fits the specific flow better than the generic version below.

| Technical term | Plain analogy |
|---|---|
| Route (`routes/*.php`) | The address/signboard that tells the app "when someone visits this page or clicks this button, go handle it here" |
| Controller / Livewire component | The staff member at the counter who actually handles your request when you walk up |
| Middleware | The security guard at the door who checks something (are you logged in? are you allowed here?) before letting you through |
| Form Request / validation | The clerk double-checking your form is filled in properly before accepting it |
| Model / Eloquent | The filing cabinet (and the person who knows how to look things up in it) for one type of record — customers, orders, products |
| Migration | The blueprint used to build or renovate the filing cabinet's shelves (the database's structure) before any records go in |
| Database / table | The filing cabinet itself; each table is one drawer for one kind of record |
| Job / Queue | A task written on a sticky note and handed to a back-office worker to do later, so the customer isn't kept waiting at the counter |
| Event / Listener | Someone rings a bell when X happens, and everyone who cares about X hears it and reacts — the bell-ringer doesn't need to know who's listening |
| Mail / Notification | The letter or SMS the app sends out to tell someone something happened |
| Policy / Authorization | The rulebook the guard uses to decide who's allowed to do what (e.g. only the shop owner can delete a product) |
| Session / Auth guard | The wristband you get at check-in that proves who you are for the rest of your visit |
| Cache | A cheat-sheet kept at the front counter so the staff don't have to walk to the filing cabinet every single time for the same answer |
| API / endpoint | A specific counter window dedicated to one kind of request, usually used by another computer program rather than a person |
| Seeder / Factory (tests) | A pretend customer and pretend records used for rehearsal, so staff can practice without touching real customer data |
| Transaction (DB) | Doing several filing steps as "all or nothing" — like a bank transfer where either both accounts update, or neither does, never half-done |
