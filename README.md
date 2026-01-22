# Content Elements

A Symfony Bundle, designed to integrate closely with API Platform and Doctrine, which adds small "elements" of logic to help provide Content Management System (CMS) functionality.

A core principle is for this library to remain flexible and out of your way:

- It works with your regular Doctrine entities, with only minimal code changes needed to "upgrade" an entity to support the chosen functionality.
- Any database changes that do occur are the minimal ones which would be needed to support the functionality - the bundle doesn't bloat your database with excess tables or fields.
- There are several different features available, which can be used in isolation, or combined together.
- The abstractions provided are highly flexible, with extension points for hooking in your own logic, or relatively easy routes to replace with your own bespoke implementations.
- The implementations are just automatically providing the "glue code" (e.g. query building) which you'd otherwise have to tediously write for each endpoint.

## Core concepts

### Dimensions

A common pattern in CMSs is to have multiple database rows for a single "thing", e.g. for content that has been translated into multiple languages, or is maintaining a version history.

We represent this by referring to the base entity as the **Identity** (e.g. `src/Entity/Author`), which can have **Variants** (e.g. `src/Entity/AuthorVariant`), which contains the fields that may be different based on **Dimensions**.  Built in Dimensions:

- **Locale** - For making content available in multiple langauages.
- **Stage** - For having staging/production versions of content in the same database (making them promoteable).
- **Version** - For tracking a version history.

### Filters

While Dimensions can lead to multiple rows for a single entity, **Filters** simply hide content from users based on certain conditions.  Built in Filters:

- **Archivable** - Allow content to be "soft-deleted" so it doesn't show up in lists by default, but can still be easily restored.
- **Publishable** - Let content be in a draft state, or scheduled for published, before it appears publicly to users.

## Usage

### Basic Identity/Variant Setup

The `#[Identity]` attribute marks an entity as having variants. By default, it expects:
- A `$variants` collection on the Identity
- An `$identity` property on each Variant pointing back
- A `$variant` property on the Identity where the resolved variant gets hydrated

```php
// src/Entity/Author.php
#[Identity(variantClass: AuthorVariant::class)]
class Author
{
    public string $id;
    public string $email;  // Shared across all locales

    #[OneToMany(targetEntity: AuthorVariant::class, mappedBy: 'identity')]
    public Collection $variants;

    public AuthorVariant $variant;  // Hydrated by Provider
}

// src/Entity/AuthorVariant.php
class AuthorVariant
{
    use LocaleDimensionTrait;  // Adds $locale

    #[ManyToOne(inversedBy: 'variants')]
    public Author $identity;

    public string $bio;  // Locale-specific
}
```

### Custom Property Names

The `#[Identity]` attribute supports custom property names, which is essential for multi-level hierarchies or when your naming conventions differ:

```php
#[Identity(
    variantClass: AuthorVariant::class,
    variantsProperty: 'translations',    // Collection property name (default: 'variants')
    identityProperty: 'author',          // Back-reference property name (default: 'identity')
    variantProperty: 'translation',      // Hydrated variant property name (default: 'variant')
)]
class Author
{
    #[OneToMany(targetEntity: AuthorVariant::class, mappedBy: 'author')]
    public Collection $translations;

    public AuthorVariant $translation;  // Hydrated by Provider
}

class AuthorVariant
{
    #[ManyToOne(inversedBy: 'translations')]
    public Author $author;  // Matches identityProperty
}
```

### Multi-Level Hierarchies (Separate Version History)

The Identity attribute is composable - a Variant can itself be an Identity with its own Variants. This enables powerful patterns like having separate version history for shared data vs locale-specific data:

```php
// Level 1: Stable identity (just an ID, never changes)
#[Identity(
    variantClass: ContentVersion::class,
    variantsProperty: 'versions',
    identityProperty: 'contentIdentity',
    variantProperty: 'version',
)]
class ContentIdentity
{
    public string $id;

    #[OneToMany(targetEntity: ContentVersion::class, mappedBy: 'contentIdentity')]
    public Collection $versions;

    public ContentVersion $version;      // Active version (hydrated)
    public ContentLocalised $localised;  // Shortcut to version->localised
}

// Level 2: Versioned shared data (thumbnail, tags, etc.)
#[Identity(
    variantClass: ContentLocalised::class,
    variantsProperty: 'localisedVariants',
    identityProperty: 'contentVersion',
    variantProperty: 'localised',
)]
class ContentVersion
{
    use VersionDimensionTrait;  // Adds $version

    #[ManyToOne(inversedBy: 'versions')]
    public ContentIdentity $contentIdentity;

    public ?MediaUpload $thumbnail = null;  // Shared, versioned
    public Collection $tags;                 // Shared, versioned

    #[OneToMany(targetEntity: ContentLocalised::class, mappedBy: 'contentVersion')]
    public Collection $localisedVariants;

    public ContentLocalised $localised;  // Active locale (hydrated)
}

// Level 3: Locale-specific, versioned content
class ContentLocalised
{
    use LocaleDimensionTrait;   // Adds $locale
    use VersionDimensionTrait;  // Adds $version (locale-level versioning)

    #[ManyToOne(inversedBy: 'localisedVariants')]
    public ContentVersion $contentVersion;

    public string $title;   // Locale-specific, versioned
    public string $body;    // Locale-specific, versioned
}
```

This structure allows:
- **Identity-level versioning**: When `thumbnail` or `tags` change, create a new `ContentVersion`
- **Locale-level versioning**: When `title` or `body` change, create a new `ContentLocalised`
- **Independent histories**: English content can have 5 versions while French has 3
