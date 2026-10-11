# EVE brand

Name: EVE, inspired by Everest. Slogan: **Reach higher. Walk further.** Nepali: **उचाइ छुनुहोस्। अझ टाढा हिँड्नुहोस्।** The mountain mark is maintained as a native SVG/Vue component.

Hero asset: `public/images/eve-everest-hero.webp`, converted to WebP at quality 85 for browser delivery. Generated with the built-in imagegen tool using the imagegen skill. This is an original imagined brand landscape, not a documentary location photograph or a shoe product photo. Catalog product images remain staff-managed uploads.

Final generation prompt:

> Use case: ads-marketing. Asset type: wide website brand hero for EVE, a Nepal retail and wholesale shoe store whose name evokes Everest. Create an original Everest-inspired Himalayan mountain vista, photography-inspired editorial artwork with a majestic snowy summit, detailed rock ridges, drifting high-altitude clouds and restrained dawn light. Wide landscape composition, mountain slightly to right with quiet sky to left, suitable for a cropped right-hand panel beside website copy. Premium outdoor editorial look, deep midnight navy and icy blue, natural white snow, a subtle warm dawn glow. Crisp realistic texture, restrained cinematic grading. No text, no logos, no shoes or products, no people, no buildings, no watermark. This is an imagined brand landscape, not a documentary image of an exact location. Landscape aspect ratio approximately 3:2.

## Language behavior

English and Nepali are available across the shop, staff workspace, authentication,
and account settings. EVE and “Reach higher. Walk further.” stay in English in
both modes. `lang/ne.json` is the shared Vue/Laravel copy catalog; validation,
authentication, password, and pagination messages live in `lang/ne/`.

Translate complete sentences with named parameters, not concatenated fragments.
Keep route paths, enum values, email addresses, SKUs, and order references intact;
translate their display labels only. Nepali mode uses Devanagari numerals for
quantities and localized currency/date formatting. The HTML language follows
Inertia navigation, and browser form validation uses the selected language.

The six demonstration products have Nepali fallback copy and searchable Nepali
names. Staff can enter an optional Nepali name and description when creating or
editing products. Custom content is editorial: supply its Nepali fields to show
it in Nepali; an empty field retains the original content. Customer names,
addresses, email addresses, uploaded filenames, and transaction identifiers are
never automatically rewritten.

New order email outbox records save the checkout language. Later messages reuse
that original language even if staff work in another language. Older orders with
no saved locale use the language active when their next message is recorded.

`npm run test:frontend` checks translation coverage, placeholders, enum values,
numerals, and rendering of the actual Inertia pages without a running HTTP server.
`NepaliExperienceTest` covers catalog/search/cart, server errors, staff product
content, account notifications, and order email locale preservation.
