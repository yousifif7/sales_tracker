<?php

return [
    'signature' => [
        'name' => env('OUTREACH_SIGNATURE_NAME', env('MAIL_FROM_NAME', 'Yousif Elfarra')),
        'title' => env('OUTREACH_SIGNATURE_TITLE', 'FieldLine — white-label security control room'),
        'website' => env('OUTREACH_SIGNATURE_WEBSITE', 'https://fieldline.yousiffarra.com'),
        'email' => env('OUTREACH_SIGNATURE_EMAIL', env('MAIL_FROM_ADDRESS', 'yousif@yousiffarra.com')),
    ],

    /*
    |--------------------------------------------------------------------------
    | Automated outreach sequence (UK business days)
    |--------------------------------------------------------------------------
    |
    | Cold email is sent manually. Follow-up / nudge / exit are scheduled from
    | enrolled_at using Mon–Fri only (Europe/London by default).
    |
    */
    'sequence' => [
        'timezone' => env('OUTREACH_SEQUENCE_TIMEZONE', 'Europe/London'),
        'send_hour' => (int) env('OUTREACH_SEQUENCE_SEND_HOUR', 9),
        'followup_business_days' => 4,
        'nudge_business_days' => 8,
        'exit_business_days' => 15,
        'followup_template' => 'fieldline_followup',
        'nudge_template' => 'fieldline_final_nudge',
        'hot_open_min_total_opens' => 5,
        'hot_open_min_unique_emails' => 2,
    ],

    'templates' => [
        'fieldline_cold' => [
            'label' => 'FieldLine — own vs rent (cold)',
            'active' => true,
            'subject' => 'Own vs rent — control room for {{company}}',
            'body' => <<<'BODY'
Hi {{first_name}},

Most UK guarding firms rent their control-room software every month. FieldLine is different — a white-label platform you own: live GPS, rota, digital DOB, incidents, client portal, and a guard app.

Demo: https://fieldline.yousiffarra.com

If that sounds useful for {{company}}, reply with a time that works for a 15-min video walkthrough.

Best,
Yousif Elfarra
BODY,
        ],
        'fieldline_followup' => [
            'label' => 'FieldLine — follow-up (3 days)',
            'active' => true,
            'subject' => 'Re: Own vs rent — control room for {{company}}',
            'body' => <<<'BODY'
Hi {{first_name}},

Quick bump in case this got buried.

A lot of multi-site firms I speak to are still running shifts on WhatsApp and incidents on paper. FieldLine puts tracking, DOB, and client reporting into one control room you own — not a monthly SaaS rent.

Demo: https://fieldline.yousiffarra.com

Worth a 15-min video look for {{company}}? Reply with a time that works.

Best,
Yousif
BODY,
        ],
        'fieldline_final_nudge' => [
            'label' => 'FieldLine — final nudge (7 days)',
            'active' => true,
            'subject' => 'Re: Own vs rent — control room for {{company}}',
            'body' => <<<'BODY'
Hi {{first_name}},

Last note from me.

FieldLine is a one-time white-label license (typically around the $10k mark depending on scope), not another monthly control-room subscription. Happy to show the live demo on a short video call — or reply "call" and we can arrange it.

If now isn't the right time, no worries at all.

https://fieldline.yousiffarra.com

Best,
Yousif
BODY,
        ],
        'fieldline_attention' => [
            'label' => 'General Attention email',
            'active' => true,
            'subject' => 'For ops / MD — control room software for {{company}}',
            'body' => <<<'BODY'
Hi,

Could you please forward this to whoever handles ops / control room systems at {{company}}?

I've built FieldLine — a white-label security control room + guard app (live GPS, rota, digital DOB, incidents, client portal) that firms own instead of renting monthly SaaS.

Demo: https://fieldline.yousiffarra.com

Happy to do a 15-min video walkthrough if useful.

Thanks,
Yousif Elfarra
BODY,
        ],
        'fieldline_multi_site' => [
            'label' => 'FieldLine — multi-site',
            'active' => true,
            'subject' => '{{company}} — ops visibility across your sites',
            'body' => <<<'BODY'
Hi {{first_name}},

Quick question for {{company}}: with guards across multiple sites, how does ops currently verify patrols happened — and how do clients get that evidence? Real-time, or end-of-shift / WhatsApp summaries?

FieldLine is a white-label control room you own: live GPS, rota, digital DOB, incident reports, exports, and a client portal — plus a guard app for check-ins and patrols.

Demo: https://fieldline.yousiffarra.com

Reply with a time for a 15-min video walkthrough if useful.

Best,
Yousif Elfarra
BODY,
        ],
        'fieldline_linkedin' => [
            'label' => 'LinkedIn Connect',
            'active' => true,
            'subject' => 'LinkedIn note — {{company}}',
            'body' => <<<'BODY'
Hi {{first_name}}, — built a white-label control room + guard app for UK multi-site guarding firms (own vs monthly SaaS rent). Demo: fieldline.yousiffarra.com — open to a short video walkthrough if useful?
BODY,
        ],

        // Kept for history / one-offs — inactive by default.
        'fieldline_cold_classic' => [
            'label' => 'FieldLine — cold email (classic, inactive)',
            'active' => false,
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
            'label' => 'SPL Connect — cold email (inactive)',
            'active' => false,
            'subject' => '{{company}} — white-label guard ops platform (ready to deploy)',
            'body' => <<<'BODY'
Hi {{first_name}},

I built SPL Connect — a full security workforce platform already used by a UK firm running guards across multiple sites.

You license it white-label: your logo, your domain, your brand.
Live in weeks, not a custom build from scratch.

Demo: https://splconnect.yousiffarra.com

If it looks relevant, happy to do a quick 15-min call to see if it fits how {{company}} runs sites today.

Best,
Yousif Elfarra
BODY,
        ],
        'spl_followup_3d' => [
            'label' => 'SPL Connect — follow-up (inactive)',
            'active' => false,
            'subject' => 'Re: {{company}} — quick follow-up',
            'body' => <<<'BODY'
Hi {{first_name}},

Just bumping this in case it landed at a busy time.

Demo: https://splconnect.yousiffarra.com

Best,
Yousif
BODY,
        ],
        'spl_followup_7d' => [
            'label' => 'SPL Connect — final nudge (inactive)',
            'active' => false,
            'subject' => 'Re: {{company}} — last note',
            'body' => <<<'BODY'
Hi {{first_name}},

Last note from me.

Demo: https://splconnect.yousiffarra.com

Best,
Yousif
BODY,
        ],
        'glentworth_russ' => [
            'label' => 'Priority — Russ Webster / Glentworth (inactive)',
            'active' => false,
            'subject' => 'Quick idea for Glentworth\'s guard & incident reporting',
            'body' => <<<'BODY'
Hi Russ,

Priority one-off template — prefer LinkedIn / personal follow-up now.

Best,
Yousif
BODY,
        ],
        'multi_site' => [
            'label' => 'SPL — multi-site (inactive)',
            'active' => false,
            'subject' => '{{company}} — ops visibility across your sites',
            'body' => <<<'BODY'
Hi {{first_name}},

Legacy SPL multi-site template — use FieldLine — multi-site instead.

Best,
Yousif
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
