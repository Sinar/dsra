<?php
/**
 * Digital Sovereignty Readiness Assessment - Questions Configuration
 *
 * This file contains the qualifying questions for the readiness assessment
 * Designed for quick 10-15 minute evaluations of digital sovereignty readiness
 */

return [
    'Data Sovereignty' => [
        'domain_key' => 'Domain-1',
        'description' => 'Data control, residency, and encryption sovereignty',
        'questions' => [
            [
                'id' => 'ds1',
                'text' => 'Does your organization follow all laws, regulations, and industry requirements related to where data must be stored or processed?',
                'weight' => 1,
                'tooltip' => 'Civil society organisations often serve across borders. Understanding where beneficiary data is stored helps protect privacy and comply with local laws.',
                'link' => [
                    'url' => 'https://www.dataguidance.com/jurisdictions/malaysia'
                ]
            ],
            [
                'id' => 'ds2',
                'text' => 'Do you hold and manage your own \'encryption keys\' to ensure that your service provider (like Google or Microsoft) cannot unlock or see your data?',
                'weight' => 1,
                'tooltip' => 'For sensitive data about beneficiaries, whistleblowers, or staff in high-risk regions, holding your own encryption keys prevents cloud providers from accessing that data.',
                'link' => [
                    'url' => 'https://ssd.eff.org/module/deep-dive-end-end-encryption-how-do-public-key-encryption-systems-work'
                ]
            ],
            [
                'id' => 'ds3',
                'text' => 'Can you stop sensitive data from being stored or transferred outside specific countries or regions?',
                'weight' => 1,
                'tooltip' => 'If your organisation works across multiple countries, sensitive data stored in foreign jurisdictions could put communities at risk if local laws change.'
            ]
        ]
    ],

    'Technical Sovereignty' => [
        'domain_key' => 'Domain-2',
        'description' => 'Technology independence and platform portability',
        'questions' => [
            [
                'id' => 'ts1',
                'text' => 'If a tool you rely on suddenly disappeared, could you switch to something else without major disruption?',
                'weight' => 1,
                'tooltip' => 'Non-profits often rely on donated or discounted software. If terms change, switching without disruption protects continuity of your mission-critical tools.'
            ],
            [
                'id' => 'ts2',
                'text' => 'Do you prefer tools that use open standards and APIs so your systems can connect, share data and work together freely rather than being locked into one company\'s ecosystem?',
                'weight' => 1,
                'tooltip' => 'Open standards (Kubernetes, OCI containers, POSIX) ensure portability and interoperability. Proprietary APIs create dependencies on specific vendors.'
            ],
            [
                'id' => 'ts3',
                'text' => 'Could you move your important apps to a different cloud platform without losing data or disrupting your work?',
                'weight' => 1,
                'tooltip' => 'If your current platform\'s free tier or donated licenses are discontinued, can you move your data to another provider without losing work?'
            ]
        ]
    ],

    'Operational Sovereignty' => [
        'domain_key' => 'Domain-3',
        'description' => 'Operational independence and resilience',
        'questions' => [
            [
                'id' => 'os1',
                'text' => 'Can you keep working if the cloud (Google Drive/OneDrive) tools you rely on are suddenly blocked or unavailable?',
                'weight' => 1,
                'tooltip' => 'Civil society organisations in politically sensitive environments face real risks of cloud services being blocked or restricted. Can your team keep working?'
            ],
            [
                'id' => 'os2',
                'text' => 'Do you have in-house technical expertise to manage \'sovereign infrastructure\' (systems your organisation fully owns and controls)?',
                'weight' => 1,
                'tooltip' => 'Managing sovereign systems requires specialized skills in security, compliance, and infrastructure management.'
            ],
            [
                'id' => 'os3',
                'text' => 'Do your disaster recovery plans cover geopolitical risks such as funding freezes, regulatory restrictions, or forced shutdowns?',
                'weight' => 1,
                'tooltip' => 'Civil society faces unique geopolitical risks — funding freezes, sanctions, regulatory changes. Your DR plans should account for these scenarios.'
            ]
        ]
    ],

    'Assurance Sovereignty' => [
        'domain_key' => 'Domain-4',
        'description' => 'Security, compliance, and audit control',
        'questions' => [
            [
                'id' => 'as1',
                'text' => 'Do you have the ability to independently verify the security, integrity, and reliability of your digital systems, data, and infrastructure?',
                'weight' => 1,
                'tooltip' => 'Independently verifying the security of your systems is critical for sovereignty to ensure full control of your data, maintain operational independence, and build trust through auditable, resilient infrastructure.'
            ],
            [
                'id' => 'as2',
                'text' => 'Does your organisation control the records of who accesses your systems and when – or does an external provider hold those records?',
                'weight' => 1,
                'tooltip' => 'If your email or document provider holds your audit logs, you may not have full visibility into who accessed sensitive data during an incident.'
            ],
            [
                'id' => 'as3',
                'text' => 'Are you aware of the digital \'sovereignty\' standards in your country – the laws and guidelines that give your organisation authority and control over its own data?',
                'weight' => 1,
                'tooltip' => 'Global regulations related to digital sovereignty are still evolving and vary widely but generally focus on a state\'s control over data and technology within its borders. These rules are often motivated by national security, economic interests, and the protection of citizen privacy, and they can significantly impact how companies operate internationally.'
            ]
        ]
    ],

    'Open Source' => [
        'domain_key' => 'Domain-5',
        'description' => 'Open source strategy and independence',
        'questions' => [
            [
                'id' => 'oss1',
                'text' => 'Do you have a formal policy favoring open-source software over proprietary alternatives?',
                'weight' => 1,
                'tooltip' => 'Many governments and regulated organizations mandate open source for transparency and sovereignty. Formal policies drive procurement decisions.'
            ],
            [
                'id' => 'oss2',
                'text' => 'If your free \'open-source\' software is abandoned, could you manage and update a copy of it independently?',
                'weight' => 1,
                'tooltip' => 'True software sovereignty means the ability to take ownership if upstream projects change direction or become unavailable.'
            ],
            [
                'id' => 'oss3',
                'text' => 'Do you actively contribute to strategic open-source projects important to your operations?',
                'weight' => 1,
                'tooltip' => 'Contributing to OSS communities ensures influence over project direction and builds internal expertise.'
            ]
        ]
    ],

    'Executive Oversight' => [
        'domain_key' => 'Domain-6',
        'description' => 'Strategic governance and leadership commitment',
        'questions' => [
            [
                'id' => 'eo1',
                'text' => 'Do you have an executive sponsor or steering committee (i.e. part of organization structure?) for digital sovereignty (control of your own technology and data) initiatives?',
                'weight' => 1,
                'tooltip' => 'For civil society — does your board or leadership have someone advocating for digital rights and technology independence?'
            ],
            [
                'id' => 'eo2',
                'text' => 'Does your organisation\'s strategy include goals around data control or reducing dependence on big tech platforms?',
                'weight' => 1,
                'tooltip' => 'Your organisation may already have goals around data protection or reducing dependency on big tech — those are sovereignty goals, even if you don\'t use that term.'
            ],
            [
                'id' => 'eo3',
                'text' => 'Do you have a dedicated budget allocated for sovereignty initiatives and compliance?',
                'weight' => 1,
                'tooltip' => 'Budget allocation indicates seriousness and enables execution of digital sovereignty programs.'
            ]
        ]
    ],

    'Managed Services' => [
        'domain_key' => 'Domain-7',
        'description' => 'Cloud service control and provider independence',
        'questions' => [
            [
                'id' => 'ms1',
                'text' => 'Can you restrict cloud deployments to specific regions or certified data centers?',
                'weight' => 1,
                'tooltip' => 'Regional restrictions ensure compliance with data residency laws and reduce geopolitical risk.'
            ],
            [
                'id' => 'ms2',
                'text' => 'Do you control and monitor your cloud provider\'s administrative access to your systems?',
                'weight' => 1,
                'tooltip' => 'Privileged access management ensures only authorized personnel can access systems.'
            ],
            [
                'id' => 'ms3',
                'text' => 'Have you tested or validated the ability to migrate workloads to different cloud providers?',
                'weight' => 1,
                'tooltip' => 'Regular migration testing proves portability isn\'t just theoretical.'
            ]
        ]
    ]
];
