@extends('layouts.backend.app')
@section('title', config('setting.company_name') . ' - Company Workspace')
@section('header_title', 'Company Workspace')

@section('content')
    <style>
        :root {
            --new-color-prime: #3984FB;
        }

        .cw-shell {
            background: linear-gradient(180deg, #eef4fb 0%, #f9fbff 100%);
            border: 1px solid #dbe6f3;
            border-radius: 26px;
            overflow: hidden;
            box-shadow: 0 18px 45px rgba(15, 23, 42, .08)
        }

        .cw-hero {
            background: linear-gradient(135deg, #1d3f7d 0%, #244f9e 100%);
            color: #fff;
            padding: 28px 30px 20px
        }

        .cw-head,
        .cw-brand,
        .cw-actions,
        .cw-tabs,
        .cw-grid,
        .cw-kpis,
        .cw-btns {
            display: flex;
            gap: 12px;
            flex-wrap: wrap
        }

        .cw-head {
            align-items: flex-start;
            justify-content: space-between
        }

        .cw-brand {
            align-items: center;
            gap: 16px
        }

        .cw-logo {
            width: 62px;
            height: 62px;
            border-radius: 16px;
            background: rgba(255, 255, 255, .14);
            border: 1px solid rgba(255, 255, 255, .2);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden
        }

        .cw-logo img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            background: #fff
        }

        .cw-fallback {
            font-size: 30px;
            color: #fff
        }

        .cw-title {
            margin: 0 0 4px;
            font-size: 30px;
            font-weight: 800;
            color: #fff
        }

        .cw-badge {
            display: inline-flex;
            align-items: center;
            padding: 10px 16px;
            border-radius: 999px;
            background: #ff7a2f;
            color: #fff;
            font-size: 13px;
            font-weight: 700;
            box-shadow: 0 14px 24px rgba(255, 122, 47, .28)
        }

        .cw-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 44px;
            padding: 0 16px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 14px;
            text-decoration: none !important
        }

        .cw-btn-light {
            background: #fff;
            color: #173b7a !important;
            border: 1px solid #cdddf3
        }

        .cw-btn-light:hover {
            background: #f6f9ff
        }

        .cw-btn-primary {
            background: var(--new-color-prime);
            ;
            color: #fff !important
        }

        .cw-btn-primary:hover {
            background: #163a7b
        }

        .cw-card,
        .cw-kpi {
            background: #fff;
            border: 1px solid #dce7f4;
            border-radius: 20px
        }

        .cw-label,
        .cw-kpi span {
            display: block;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .06em
        }

        .cw-tabs {
            padding: 18px 24px 0;
            overflow-x: auto
        }

        .cw-tabs .nav-link {
            border: none;
            border-radius: 14px 14px 0 0;
            padding: 14px 18px;
            background: transparent;
            color: #4b6286;
            font-weight: 700;
            font-size: 14px;
            min-width: max-content
        }

        .cw-tabs .nav-link.active {
            background: #fff;
            color: #173b7a;
            box-shadow: 0 -4px 18px rgba(15, 23, 42, .04)
        }

        .cw-tabs .nav-link i {
            margin-right: 8px
        }

        .cw-body {
            background: #fff;
            border-top: 1px solid #e6edf7;
            padding: 24px
        }

        .cw-frame-shell {
            background: #f8fbff;
            border: 1px solid #dce7f4;
            border-radius: 22px;
            overflow: hidden;
            box-shadow: 0 14px 32px rgba(15, 23, 42, .05)
        }

        .cw-frame-head {
            display: none
        }

        .cw-frame-head h4 {
            margin: 0;
            color: #102847;
            font-size: 22px;
            font-weight: 800
        }

        .cw-frame-wrap {
            position: relative;
            min-height: 320px;
            background: linear-gradient(180deg, #f8fbff 0%, #ffffff 100%)
        }

        .cw-frame-loader {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #5f7394;
            font-weight: 700;
            background: linear-gradient(180deg, rgba(248, 251, 255, .95) 0%, rgba(255, 255, 255, .95) 100%);
            z-index: 1;
            transition: opacity .2s ease
        }

        .cw-frame-loader.hidden {
            opacity: 0;
            pointer-events: none
        }

        .cw-frame {
            width: 100%;
            height: 420px;
            border: 0;
            display: block;
            background: #fff
        }

        .cw-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.45fr) minmax(280px, .9fr);
            gap: 20px
        }

        .cw-card {
            padding: 24px;
            box-shadow: 0 14px 32px rgba(15, 23, 42, .05)
        }

        .cw-card.soft {
            background: linear-gradient(180deg, #f8fbff 0%, #ffffff 100%)
        }

        .cw-label {
            color: #5f7394;
            margin-bottom: 10px
        }

        .cw-card h4 {
            margin: 0 0 8px;
            color: #102847;
            font-size: 18px;
            font-weight: 800
        }

        .cw-card p {
            margin: 0;
            color: #64748b;
            font-size: 14px;
            line-height: 1.7
        }

        .cw-kpis {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
            margin-top: 22px
        }

        .cw-kpi {
            padding: 16px 18px;
            background: #f8fbff
        }

        .cw-kpi span {
            color: #64748b;
            margin-bottom: 6px
        }

        .cw-kpi strong {
            color: #102847;
            font-size: 26px;
            line-height: 1;
            font-weight: 800
        }

        .cw-kpi small {
            display: block;
            margin-top: 7px;
            color: #7f8da3;
            font-size: 12px
        }

        .cw-btns {
            margin-top: 22px
        }

        .cw-list {
            list-style: none;
            padding: 0;
            margin: 18px 0 0
        }

        .cw-list li {
            display: flex;
            gap: 10px;
            padding: 10px 0;
            border-top: 1px solid #edf2f8;
            color: #334155;
            font-size: 14px;
            line-height: 1.7
        }

        .cw-list li:first-child {
            border-top: none;
            padding-top: 0
        }

        .cw-list i {
            color: var(--new-color-prime);
            ;
            font-size: 18px;
            margin-top: 3px
        }

        .cw-pill {
            display: inline-flex;
            align-items: center;
            padding: 6px 12px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700
        }

        .cw-pill.ok {
            color: #166534;
            background: #ecfdf3
        }

        .cw-pill.warn {
            color: #b45309;
            background: #fff7ed
        }

        @media (max-width:1199px) {
            .cw-grid {
                grid-template-columns: 1fr
            }
        }

        @media (max-width:767px) {
            .cw-hero {
                padding: 24px 18px 18px
            }

            .cw-tabs {
                padding: 16px 14px 0
            }

            .cw-body {
                padding: 20px 14px 24px
            }

            .cw-kpis {
                grid-template-columns: 1fr
            }
        }
    </style>

    @php
        $tabConfig = [
            'freight-calculator' => ['label' => 'Freight Calculator', 'icon' => 'mdi mdi-calculator-variant-outline', 'title' => 'Freight Calculator', 'src' => route('rate.calculator', ['company_id' => $shippingCompany->id, 'embed' => 1])],
            'zone-matrix' => ['label' => 'Zone Matrix', 'icon' => 'mdi mdi-map-marker-path', 'title' => 'Zone Matrix', 'src' => route('rate.zone-setting', ['shipping_company_id' => $shippingCompany->id, 'embed' => 1])],
            'admin-rate-matrix' => ['label' => 'Admin Rate Matrix', 'icon' => 'mdi mdi-table-large', 'title' => 'Admin Rate Matrix', 'src' => route('rate.price-list', ['type' => 'b2c', 'shipping_company_id' => $shippingCompany->id, 'rate_card_id' => $defaultB2cRateCardId, 'weight' => $defaultB2cWeight, 'embed' => 1])],
            'manage-pincodes' => ['label' => 'Manage Pincodes', 'icon' => 'mdi mdi-map-marker-radius', 'title' => 'Manage Pincodes', 'src' => route('shipping.company.manage-pincode', ['id' => $shippingCompany->id, 'embed' => 1])],
            'other-charges-master' => ['label' => 'Other Charges Master', 'icon' => 'mdi mdi-cash-multiple', 'title' => 'Other Charges Master', 'src' => route('rate.other-charges-master', ['shipping_company_id' => $shippingCompany->id, 'embed' => 1])],
        ];
    @endphp

    <div class="content-page">
        <div class="content">
            <div class="container-fluid">
                <div class="cw-shell">
                    <div class="cw-hero">
                        <div class="cw-head">
                            <div class="cw-brand">
                                <div class="cw-logo">
                                    @if($shippingCompany->logo)
                                        <img src="{{ url('storage/shipping-logo/' . $shippingCompany->logo) }}"
                                            alt="{{ $shippingCompany->name }}">
                                    @else
                                        <i class="mdi mdi-truck-fast cw-fallback" aria-hidden="true"></i>
                                    @endif
                                </div>
                                <div>
                                    <h3 class="cw-title">{{ $shippingCompany->name }}</h3>
                                </div>
                            </div>
                            <div class="cw-actions">
                                <div class="cw-badge">{{ $companyMeta['freight_mode_label'] }} Mode Panel</div>
                                <a href="{{ route('shipping.company') }}" class="cw-btn cw-btn-light"><i
                                        class="mdi mdi-arrow-left"></i>Back</a>
                            </div>
                        </div>

                    </div>

                    <ul class="nav cw-tabs" id="companyWorkspaceTabs" role="tablist">
                        @foreach($tabConfig as $tabKey => $tab)
                            <li class="nav-item">
                                <a class="nav-link {{ $activeTab === $tabKey ? 'active' : '' }}" id="tab-{{ $tabKey }}"
                                    href="#pane-{{ $tabKey }}" role="tab" data-tab="{{ $tabKey }}"
                                    data-pane="pane-{{ $tabKey }}"><i class="{{ $tab['icon'] }}"></i>{{ $tab['label'] }}</a>
                            </li>
                        @endforeach
                    </ul>

                    <div class="cw-body">
                        <div class="cw-frame-shell">
                            <div class="cw-frame-head">
                                <h4 id="companyWorkspaceTitle">{{ $tabConfig[$activeTab]['title'] ?? 'Company Workspace' }}
                                </h4>
                            </div>
                            <div class="cw-frame-wrap">
                                <div class="cw-frame-loader" id="companyWorkspaceLoader">Loading
                                    {{ $tabConfig[$activeTab]['label'] ?? 'workspace' }}...
                                </div>
                                <iframe class="cw-frame" id="companyWorkspaceFrame"
                                    data-src="{{ $tabConfig[$activeTab]['src'] ?? '' }}"></iframe>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')
    <script>
        $(function () {
            const tabConfig = @json($tabConfig);

            function resizeFrame(frame) {
                if (!frame || !frame.contentWindow) {
                    return;
                }

                try {
                    const doc = frame.contentWindow.document;
                    const body = doc.body;
                    const html = doc.documentElement;
                    const selectors = [
                        '.shipping_cost:visible',
                        '.calc-right-content',
                        '.content-page',
                        '.embedded-layout',
                        'body > *:last-child'
                    ];

                    let contentBottom = 0;
                    selectors.forEach(function (selector) {
                        const $candidate = $(doc).find(selector).first();
                        if (!$candidate.length || !$candidate.is(':visible')) {
                            return;
                        }

                        const element = $candidate.get(0);
                        const rect = element.getBoundingClientRect();
                        const top = rect.top + (frame.contentWindow.pageYOffset || html.scrollTop || body.scrollTop || 0);
                        const bottom = top + Math.max(element.scrollHeight || 0, rect.height || 0);
                        contentBottom = Math.max(contentBottom, bottom);
                    });

                    const fallbackHeight = Math.max(body ? body.scrollHeight : 0, html ? html.scrollHeight : 0, 260);
                    const nextHeight = Math.max(contentBottom, fallbackHeight > 0 ? Math.min(fallbackHeight, contentBottom + 24) : 0, 260);
                    frame.style.height = nextHeight + 'px';
                } catch (error) {
                    frame.style.height = '420px';
                }
            }

            function loadFrame(src, label) {
                const $frame = $('#companyWorkspaceFrame');
                const $loader = $('#companyWorkspaceLoader');
                const frame = $frame.get(0);
                if (!frame || !src) {
                    return;
                }

                $loader.text('Loading ' + label + '...');
                $loader.removeClass('hidden');
                $frame.off('load.workspace').on('load.workspace', function () {
                    resizeFrame(frame);
                    $loader.addClass('hidden');
                });

                if (frame.getAttribute('src') !== src) {
                    frame.setAttribute('src', src);
                    return;
                }

                if (frame.contentWindow && frame.getAttribute('src') === src) {
                    resizeFrame(frame);
                    $loader.addClass('hidden');
                }
            }

            function switchTab(tabKey) {
                const $targetLink = $('#companyWorkspaceTabs a[data-tab="' + tabKey + '"]');
                const config = tabConfig[tabKey];
                if (!$targetLink.length || !config) {
                    return;
                }

                $('#companyWorkspaceTabs .nav-link').removeClass('active');
                $targetLink.addClass('active');
                $('#companyWorkspaceTitle').text(config.title);

                const nextUrl = new URL(window.location.href);
                nextUrl.searchParams.set('tab', tabKey);
                window.history.replaceState({}, '', nextUrl.toString());

                loadFrame(config.src, config.label);
            }

            $('#companyWorkspaceTabs').on('click', 'a[data-tab]', function (event) {
                event.preventDefault();
                switchTab($(this).data('tab'));
            });

            switchTab(@json($activeTab));

            setInterval(function () {
                const activeFrame = $('#companyWorkspaceFrame').get(0);
                if (activeFrame) {
                    resizeFrame(activeFrame);
                }
            }, 1200);
        });
    </script>
@endpush