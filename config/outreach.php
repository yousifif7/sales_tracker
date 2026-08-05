<?php

return [
    'signature' => [
        'name' => env('OUTREACH_SIGNATURE_NAME', env('MAIL_FROM_NAME', 'Yousif Elfarra')),
        'title' => env('OUTREACH_SIGNATURE_TITLE', 'FieldLine — white-label security control room'),
        'website' => env('OUTREACH_SIGNATURE_WEBSITE', 'https://fieldline.yousiffarra.com'),
        'email' => env('OUTREACH_SIGNATURE_EMAIL', env('MAIL_FROM_ADDRESS', 'yousif@yousiffarra.com')),
    ],

    'templates' => [
        'fieldline_cold' => [
            'label' => 'FieldLine — cold email',
            'subject' => 'Control room platform for {{company}}',
            'body' => <<<'BODY'
Hi {{first_name}},

I've built FieldLine, a control-room platform for security firms — scheduling, live site tracking, digital DOB, incident reports, invoicing, and a branded client portal, plus a guard mobile app for check-ins, patrols, and panic alerts.

You can see it here: https://fieldline.yousiffarra.com

Would you be open to a quick call to walk through it and see if it's a fit for {{company}}?

Best,
Yousif Elfarra
BODY,
        ],
        'spl_cold' => [
            'label' => 'SPL Connect — cold email',
            'subject' => '{{company}} — white-label guard ops platform (ready to deploy)',
            'body' => <<<'BODY'
Hi {{first_name}},

I built SPL Connect — a full security workforce platform already used by a UK firm running guards across multiple sites.

You license it white-label: your logo, your domain, your brand.
Live in weeks, not a custom build from scratch.

What you get:
- Live GPS guard tracking for your ops / control room
- Shift / site scheduling and rota
- Incident reporting + Daily Occurrence Book
- Check-calls, patrols, SIA / compliance documents
- Report exports (Excel/PDF) your team can send to clients
- Client portal (rota, sites, invoices under your brand)
- REST APIs ready if you want a guard mobile app later
- Full source code available — you own your copy

Demo: https://splconnect.yousiffarra.com

If it looks relevant, happy to do a quick 15-min call to see if it fits how {{company}} runs sites today.

Best,
Yousif Elfarra
yousiffarra.com
BODY,
        ],
        'spl_followup_3d' => [
            'label' => 'SPL Connect — follow-up (3 days)',
            'subject' => 'Re: {{company}} — quick follow-up',
            'body' => <<<'BODY'
Hi {{first_name}},

Just bumping this in case it landed at a busy time.

A lot of firms I speak to are still running shifts on WhatsApp and incidents on paper. The short demo shows how one UK firm put tracking, incidents, and client reporting into one control room.

Happy to walk you through it if useful — no pressure either way.

Demo: https://splconnect.yousiffarra.com

Best,
Yousif
BODY,
        ],
        'spl_followup_7d' => [
            'label' => 'SPL Connect — final nudge (7 days)',
            'subject' => 'Re: {{company}} — last note',
            'body' => <<<'BODY'
Hi {{first_name}},

Last note from me — I recorded a short walkthrough of live guard tracking for ops teams, plus report exports you can send to clients, all under your own brand.

No commitment. Just thought it might be useful for {{company}}.

If now's not the time, totally fine.

Demo: https://splconnect.yousiffarra.com

Best,
Yousif
BODY,
        ],
        'glentworth_russ' => [
            'label' => 'Priority — Russ Webster / Glentworth',
            'subject' => 'Quick idea for Glentworth\'s guard & incident reporting',
            'body' => <<<'BODY'
Hi Russ,

I came across Glentworth while looking at UK security firms and noticed the personal, tailored approach you've built since 2000 — that stood out.

I'm a backend developer who built a guard management platform for a UK security company: live map tracking for the ops team, incident reports with photos/timestamps, and report exports you can send to clients. I'm now offering it white-label so other firms can run the same system without a TrackTik-style monthly subscription.

Here's a short walkthrough — no pressure, just in case it helps how you currently handle reporting and oversight:

https://splconnect.yousiffarra.com

If it looks useful, happy to jump on 15 minutes and talk through what it would look like for Glentworth. If not, no worries at all.

Appreciate you taking a look either way.

Best,
Yousif Elfarra
yousif@yousiffarra.com
yousiffarra.com
BODY,
        ],
        'multi_site' => [
            'label' => 'SPL — multi-site patrol visibility',
            'subject' => '{{company}} — ops visibility across your sites',
            'body' => <<<'BODY'
Hi {{first_name}},

I came across {{company}} while researching guard companies covering multiple sites. Impressive coverage.

Quick question: with guards spread across that many locations, how does your ops team currently verify patrols happened — and how do clients get that evidence? Real-time updates, or end-of-shift / WhatsApp summaries?

I built SPL Connect for a UK security firm managing guards across multiple sites. Before this, they used WhatsApp groups and paper logbooks. Now everything sits in one control room:

• Live GPS tracking for your ops team
• Shift / site scheduling and rota
• Incident reporting + Daily Occurrence Book
• Report exports (Excel/PDF) you can send to clients
• Client portal — rota, sites, invoices under your brand

It's white-label: your logo, your domain, your brand.

Demo: https://splconnect.yousiffarra.com

Are you open to a 15-minute call to see how it could work for {{company}}'s multi-site setup?

Best,
Yousif Elfarra
yousif@yousiffarra.com
BODY,
        ],
    ],

    'lead_search_presets' => [
        'uk_security_midlands' => [
            'label' => 'UK security firms — Midlands (FieldLine ICP)',
            'criteria' => <<<'CRITERIA'
Find UK security guarding companies that match this ICP for FieldLine / SPL Connect outreach:

Ideal profile:
- UK security guarding / manned guarding company
- Roughly 10-80 employees (guards), sweet spot mid-size
- Still running ops manually (WhatsApp, spreadsheets, paper DOBs, phone scheduling)
- NOT already using dedicated guard-management / TrackTik-style software

Region focus this run: Midlands / Birmingham / Leicester / Nottingham / Coventry

Disqualify / skip:
- Firms marketing "real-time KPI monitoring", "guard track system", "live tracking", dedicated ops software
- Very large firms (200+ employees)
- No verifiable website / no findable named decision-maker

For each lead return:
- name: owner, founder, MD, or director (verify they currently work there)
- role: their job title
- company: company name
- email: public contact email if available (prefer named email, else general company email)
- website: company website URL
- linkedin_url: personal LinkedIn profile URL
- company_linkedin_url: company LinkedIn page if found
- social_links: instagram/facebook/x if found
- source_url: page used to verify

Return strict JSON only, max 10 leads. Prefer fresh companies not already widely known mega-brands.
Prefer real https links. Never invent names, emails, or LinkedIn URLs — use JSON null if unverified. Prefer fewer real leads over padding with guesses.
CRITERIA
        ],
        'uk_security_wales_scotland' => [
            'label' => 'UK security firms — Wales / Scotland',
            'criteria' => <<<'CRITERIA'
Find UK security guarding companies that match this ICP for FieldLine / SPL Connect:

Ideal: 10-80 employee guarding firms still on WhatsApp/spreadsheets/paper DOBs.
Region focus: North Wales, Bangor area, Scotland.
Skip firms advertising live tracking / KPI dashboards / enterprise ops software.
Skip 200+ employee firms and any without a named decision-maker.

Return name (owner/MD/director), company, email if public, source_url. Strict JSON, max 10.
CRITERIA,
        ],
        'uk_security_north' => [
            'label' => 'UK security firms — North of England',
            'criteria' => <<<'CRITERIA'
Find North of England security guarding companies (Manchester, Leeds, Liverpool, Newcastle, Sheffield) matching:

- 10-80 guards/employees
- Traditional ops (manual scheduling, paper DOB, WhatsApp)
- No dedicated guard-management platform in their marketing
- Named decision-maker (owner, founder, MD, director) with public email or company contact email

Skip Uniguard-like firms that already advertise systems innovation / real-time KPI monitoring.
Strict JSON array only, max 10 leads with name, company, email, source_url.
CRITERIA,
        ],
        'construction_security' => [
            'label' => 'Construction-site security firms (UK)',
            'criteria' => <<<'CRITERIA'
Find UK security firms that specialize in construction site guarding / site security.

ICP: 10-80 employees, multi-site construction coverage, likely need incident evidence with photos/timestamps for clients.
Skip firms already selling their own live tracking / ops software stack.
Prefer owners/MDs with public emails.

Return strict JSON: name, company, email, source_url. Max 10.
CRITERIA,
        ],
        'event_security' => [
            'label' => 'Event / door supervision firms (UK)',
            'criteria' => <<<'CRITERIA'
Find UK event security / door supervision / festival security companies (10-80 staff) that coordinate multiple events and likely still use WhatsApp/spreadsheets for assignments.

Need named ops decision-maker (owner/MD/director), public email if available, company website URL.
Skip enterprise/software-forward brands.

Strict JSON only: name, company, email, source_url. Max 10.
CRITERIA,
        ],
    ],
];
