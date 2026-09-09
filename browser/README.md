<p class="eyebrow">Android Debloat List v{{VERSION}} · SysAdminDoc fork</p>

# Look up a package before you change it.

<p class="lead">Read the recorded purpose and removal classification for an Android package. Start with the exact package ID, then check the notes against your device.</p>

<div class="metrics">
<div><strong>{{PACKAGES}}</strong><span>package records across five lists</span></div>
<div><strong>{{SUGGESTION_LISTS}}</strong><span>replacement categories, some still empty</span></div>
</div>

This is an offline reference, not a debloating app. It can't inspect your phone or remove anything.

## Find an entry

Use the search button at the top of the page. Try `com.facebook.appmanager` or `com.android.systemui`. Choose a result by its package ID, since several apps share the same label.

Browse [AOSP](categories/aosp.md), [Carrier](categories/carrier.md), [Google](categories/google.md), [Other packages](categories/misc.md) or [Device makers](categories/oem.md) in the sidebar.

<aside class="risk-note"><strong>A classification isn't a safety guarantee.</strong><p>Device builds differ. Missing notes or dependencies don't mean removal is safe. Read the warning and keep a recovery plan before changing a device.</p></aside>

## Know which snapshot you're reading

The package data was last changed in this fork on **2026-05-14**. v{{VERSION}} updates the reference presentation and packaging, not the underlying recommendations.

The [SysAdminDoc fork](https://github.com/SysAdminDoc/android-debloat-list) includes records from [Muntashir Al-Islam's Android Debloat List](https://github.com/MuntashirAkon/android-debloat-list) and UAD-NG. It isn't automatically synchronized with either project.

[How to read an entry](reading-guide.md) explains the classification values and missing information. [License and origins](license.md) covers attribution.
