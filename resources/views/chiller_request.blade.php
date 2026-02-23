<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Refrigerator Agreement</title>
    <style>
        @page {
            size: A4;
            margin: 10mm;
            /* Reduced page margin to allow for the border container */
        }

        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 11px;
            line-height: 1.5;
            color: #333;
            margin: 0;
            padding: 0;
        }

        /* Page Border Container */
        .page-border {
            border: 2px solid #cc0000;
            /* Red Border */
            padding: 20px;
            /* Inner spacing */
            margin: 10px;
            /* Outer spacing from page edge */
            min-height: 95vh;
            /* Ensure it takes up most of the page */
        }

        /* Typography & Colors */
        h1,
        h2,
        h3,
        h4 {
            color: #cc0000;
            /* Red */
            margin: 0;
        }

        .text-uppercase {
            text-transform: uppercase;
        }

        .text-bold {
            font-weight: bold;
        }

        .text-center {
            text-align: center;
        }

        .text-justify {
            text-align: justify;
        }

        .text-right {
            text-align: right;
        }

        .color-primary {
            color: #cc0000;
        }

        /* Red */
        .color-secondary {
            color: #555;
        }

        /* Layout Utilities */
        .w-100 {
            width: 100%;
        }

        .mb-1 {
            margin-bottom: 5px;
        }

        .mb-2 {
            margin-bottom: 10px;
        }

        .mb-3 {
            margin-bottom: 15px;
        }

        .mt-1 {
            margin-top: 5px;
        }

        .mt-2 {
            margin-top: 10px;
        }

        .mt-3 {
            margin-top: 15px;
        }

        .no-break {
            page-break-inside: avoid;
        }

        /* Header */
        .header-table {
            width: 100%;
            border-bottom: 3px solid #cc0000;
            /* Red */
            padding-bottom: 15px;
            margin-bottom: 20px;
        }

        .company-name {
            font-size: 20px;
            font-weight: 800;
            color: #cc0000;
            /* Red */
            letter-spacing: 0.5px;
        }

        .contact-info {
            font-size: 9px;
            color: #555;
            margin-top: 5px;
        }

        .doc-meta {
            font-size: 9px;
            color: #777;
            line-height: 1.4;
        }

        /* Title Block */
        .title-block {
            background-color: #fff5f5;
            /* Light Red tint */
            border: 1px solid #ffcccc;
            /* Light Red border */
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }

        .republic-title {
            font-size: 12px;
            font-weight: bold;
            letter-spacing: 1px;
            margin-bottom: 5px;
        }

        .act-title {
            font-size: 10px;
            color: #555;
            margin-bottom: 2px;
        }

        .main-subject {
            font-size: 14px;
            font-weight: bold;
            color: #cc0000;
            /* Red */
            margin-top: 10px;
            border-top: 1px solid #ffcccc;
            padding-top: 10px;
        }

        /* Content Sections */
        .section-title {
            font-size: 12px;
            font-weight: bold;
            color: #cc0000;
            /* Red */
            text-transform: uppercase;
            margin-top: 15px;
            margin-bottom: 5px;
            border-bottom: 1px solid #ffcccc;
            padding-bottom: 2px;
        }

        .clause-content {
            margin-bottom: 10px;
        }

        .sub-list {
            margin-top: 5px;
            margin-bottom: 5px;
            padding-left: 20px;
        }

        .sub-list li {
            margin-bottom: 4px;
        }

        /* Tables */
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
            font-size: 11px;
        }

        .info-table td {
            padding: 6px 10px;
            border: 1px solid #ffcccc;
        }

        .info-table .label-col {
            background-color: #fff5f5;
            font-weight: bold;
            width: 30%;
            color: #cc0000;
            /* Red */
        }

        /* Signatures */
        .signature-section {
            margin-top: 30px;
            border-top: 2px solid #cc0000;
            /* Red */
            padding-top: 20px;
        }

        .sign-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 10px 0;
        }

        .sign-col {
            width: 33%;
            vertical-align: top;
            background: #fdfdfd;
            border: 1px solid #eee;
            padding: 10px;
        }

        .sign-header {
            font-size: 10px;
            font-weight: bold;
            color: #cc0000;
            /* Red */
            text-transform: uppercase;
            margin-bottom: 15px;
            min-height: 25px;
        }

        .sign-field {
            margin-bottom: 12px;
        }

        .sign-label-small {
            font-size: 9px;
            color: #777;
            margin-bottom: 2px;
        }

        .sign-line-box {
            border-bottom: 1px solid #ccc;
            min-height: 18px;
            font-weight: bold;
            font-size: 11px;
        }

        /* Footer */
        .page-footer {
            margin-top: 30px;
            padding-top: 10px;
            border-top: 1px solid #ffcccc;
            text-align: center;
            font-size: 8px;
            color: #999;
        }
    </style>
</head>

<body>
    <div class="page-border">
        <!-- Header -->
        <table class="header-table">
            <tr>
                <td width="65%" valign="top">
                    <div class="company-name text-uppercase">Hariss International Limited</div>
                    <div class="contact-info">
                        Plot 32/33, Bombo Road, Kawempe. P.O. Box 12270, Kampala Uganda<br>
                        <strong>Tel:</strong> +256 204 001 000 &nbsp;|&nbsp; <strong>Email:</strong>
                        info@harissint.com<br>
                        <strong>Website:</strong> www.harissint.com
                    </div>
                </td>
                <td width="35%" valign="top" class="text-right">
                    <div class="doc-meta">
                        <div style="margin-bottom: 3px;"><span class="text-bold">Doc. No.</span> HI-MA-F8069</div>
                        <div style="margin-bottom: 3px;"><span class="text-bold">Rev. No.</span> 0</div>
                        <div><span class="text-bold">Effective Date:</span> 10/05/2020</div>
                    </div>
                </td>
            </tr>
        </table>

        <!-- Title Block -->
        <div class="title-block text-center no-break">
            <div class="republic-title text-uppercase">The Republic of Uganda</div>
            <div class="act-title text-uppercase">In the matter of the Contracts Act No. 7 of 2010</div>
            <div class="act-title text-uppercase">And</div>
            <div class="main-subject text-uppercase">
                In the matter of provision of Riham Refrigerators for Sales Promotion
            </div>
        </div>

        <!-- Introduction -->
        <div class="text-justify mb-3">
            This Agreement is entered this <span class="text-bold">{{ $day ?? '09' }}<sup>th</sup></span> day of <span
                class="text-bold">{{ $month ?? 'October' }} {{ $year ?? '2024' }}</span>
            <br><br>
            <span class="text-bold color-primary">BETWEEN</span><br>
            <span class="text-bold">HARISS INTERNATIONAL LIMITED</span> of Plot 32/33 Bombo Road Kawempe, P.O. Box 24972
            Kampala, Uganda (hereinafter referred to as “THE COMPANY”)
            <br><br>
            <span class="text-bold color-primary">AND</span><br>
            <span class="text-bold">M/S {{ $receiving_entity_name ?? 'Cafe Restaurant' }}</span> of {{
            $receiving_entity_person ?? 'Ddamulira Colline' }} (address {{ $receiving_entity_address ?? 'Kalisizo,
            opposite,
            Kalisizo' }}) (hereinafter referred to as “THE RECEIVING ENTITY”).
        </div>

        <!-- Recitals -->
        <div class="text-justify mb-3"
            style="background-color: #fff5f5; padding: 10px; border-left: 3px solid #cc0000;">
            <strong>WHEREAS</strong>, “THE COMPANY” is a renowned foods and beverages manufacturer in Uganda and wishes
            to
            provide refrigerators to the receiving entity to display its beverages; and<br><br>
            <strong>WHEREAS</strong>, “THE RECEIVING ENTITY” distributes the company’s beverages and seeks to enhance
            service to customers at optimum consumption temperature;
        </div>

        <div class="text-center text-bold mb-3">
            NOW THEREFORE THIS AGREEMENT WITNESSETH AS FOLLOWS:
        </div>

        <!-- Clauses -->
        <div class="section-title">1. Term</div>
        <div class="clause-content text-justify">
            The term for this Agreement (the “Term”) shall run from the date of signature for a duration of three (3)
            years
            and may be renewed for such other period as the company shall deem fit and proper.
        </div>

        <div class="section-title">2. Ownership of Refrigerator(s)/Ice Cooler</div>
        <div class="clause-content text-justify">
            Ownership of the refrigerators shall remain vested in the company at all times and the receiving entity
            shall
            have no entitlement to the supplied refrigerators. Failure to return the refrigerators when recalled will
            result
            in the receiving entity being charged, and the landlord of the premises shall have no rights over the
            refrigerators at any time.
        </div>

        <div class="section-title">3. The Receiving Entity Covenants</div>
        <div class="clause-content text-justify">
            <ol class="sub-list" type="a">
                <li>The refrigerator shall be used solely for the cooling and display of the company’s beverages and
                    shall
                    not contain competitors’ items.</li>
                <li>The receiving entity shall position the refrigerator at the business location as ascertained by the
                    company and shall not relocate it without prior written notice.</li>
                <li>The receiving entity shall keep the refrigerator in good working condition, switched on 24 hours a
                    day,
                    hygienically clean, fully stocked and well positioned.</li>
                <li>The receiving entity shall not sublet or transfer the unit without notifying the company.</li>
                <li>If the refrigerator is lost, stolen or damaged during use, the receiving entity shall be fully
                    responsible for its replacement cost.</li>
                <li>The company’s merchandiser shall be permitted to arrange and maintain the refrigerator according to
                    company guidelines.</li>
            </ol>
        </div>

        <div class="section-title">4. The Company Covenants</div>
        <div class="clause-content text-justify">
            <ol class="sub-list" type="a">
                <li>Maintenance and repairs of the refrigerator shall be carried out only by a certified employee of
                    Hariss
                    International Ltd, and breach of this shall result in termination and recall of the refrigerator.
                </li>
                <li>The company reserves the right to recall or repossess the refrigerator without giving reasons.</li>
                <li>The company may terminate this contract at any time at its sole discretion without prior written
                    notice
                    or compensation.</li>
            </ol>
        </div>

        <div class="section-title">5. Liability for Use</div>
        <div class="clause-content text-justify">
            Liability for injury, disability or death caused by operation of the refrigerator shall be borne by the
            receiving entity, which shall indemnify the company from any related loss.
        </div>

        <div class="section-title">6. Warranties / Representations / Indemnity / Insurance</div>
        <div class="clause-content text-justify">
            Each party warrants that it has authority to enter into this Agreement, and the receiving entity warrants
            that
            every person working with the refrigerator is at least eighteen (18) years of age.
        </div>

        <div class="section-title">7. Compliance with Laws and Regulations</div>
        <div class="clause-content text-justify">
            The receiving entity shall comply with all applicable national laws, regulations and company policies
            relating
            to product sales and refrigerator usage.
        </div>

        <div class="section-title">8. Assignment</div>
        <div class="clause-content text-justify">
            Neither party may assign this Agreement or delegate duties without prior written consent from the other
            party.
        </div>

        <div class="section-title">9. Debarment</div>
        <div class="clause-content text-justify">
            The receiving entity warrants that it has not been and will not be debarred by any authority from providing
            services in its distributor capacity.
        </div>

        <div class="section-title">10. Successors and Assigns</div>
        <div class="clause-content text-justify">
            This Agreement shall bind and benefit the parties and their respective heirs, representatives, successors
            and
            assigns.
        </div>

        <div class="section-title">11. Entire Agreement</div>
        <div class="clause-content text-justify">
            This Agreement constitutes the entire understanding between the parties and supersedes all prior
            arrangements
            relating to its subject matter.
        </div>

        <div class="section-title">12. Obligation of Licensed Agent</div>
        <div class="clause-content text-justify">
            The licensed agent or distributor in the territory shall ensure that the receiving entity complies with
            company
            policies on stock purchases and refrigerator usage.
        </div>

        <div class="section-title">13. Asset Details</div>
        <table class="info-table no-break">
            <tr>
                <td class="label-col">Asset Number (Hariss International Ltd)</td>
                <td>{{ $asset_number ?? 'HIL/2024/350/0192' }}</td>
            </tr>
            <tr>
                <td class="label-col">Machine Serial Number</td>
                <td>{{ $serial_number ?? '31872240100042' }}</td>
            </tr>
            <tr>
                <td class="label-col">Model & Branding</td>
                <td>{{ $model_brand ?? 'Rockboom' }}</td>
            </tr>
        </table>

        <div class="section-title">14. Governing Law</div>
        <div class="clause-content text-justify">
            This Agreement shall be governed by and construed in accordance with the laws of the Republic of Uganda.
        </div>

        <!-- Signatures -->
        <div class="signature-section no-break">
            <table class="sign-table">
                <tr>
                    <td class="sign-col">
                        <div class="sign-header">For Hariss International Limited</div>

                        <div class="sign-field">
                            <div class="sign-label-small">Name & Contact No:</div>
                            <div class="sign-line-box">{{ $company_sign_name ?? 'Bassajjansola Alex 0781533910' }}</div>
                        </div>

                        <div class="sign-field">
                            <div class="sign-label-small">Signature:</div>
                            <div class="sign-line-box" style="height: 40px;"></div>
                        </div>

                        <div class="sign-field">
                            <div class="sign-label-small">Date:</div>
                            <div class="sign-line-box">{{ $company_sign_date ?? '2024-10-09' }}</div>
                        </div>
                    </td>

                    <td class="sign-col">
                        <div class="sign-header">For Receiving Entity</div>

                        <div class="sign-field">
                            <div class="sign-label-small">Name & Contact No:</div>
                            <div class="sign-line-box">{{ $receiving_sign_name ?? 'Ddamulira Colline 0754312425' }}
                            </div>
                        </div>

                        <div class="sign-field">
                            <div class="sign-label-small">Signature:</div>
                            <div class="sign-line-box" style="height: 40px;"></div>
                        </div>

                        <div class="sign-field">
                            <div class="sign-label-small">Date:</div>
                            <div class="sign-line-box">{{ $receiving_sign_date ?? '2024-10-09' }}</div>
                        </div>
                    </td>

                    <td class="sign-col">
                        <div class="sign-header">In The Presence Of</div>

                        <div class="sign-field">
                            <div class="sign-label-small">1. Sales Executive:</div>
                            <div class="sign-line-box">{{ $sales_exec ?? 'Bernard Mujuzi 0702668719' }}</div>
                            <div class="sign-line-box" style="margin-top: 5px; height: 30px;"></div>
                        </div>

                        <div class="sign-field">
                            <div class="sign-label-small">2. LC Officer / Agent:</div>
                            <div class="sign-line-box">{{ $lc_officer ?? '' }}</div>
                            <div class="sign-line-box" style="margin-top: 5px; height: 30px;"></div>
                        </div>

                        <div class="sign-field">
                            <div class="sign-label-small">3. Landlord:</div>
                            <div class="sign-line-box">{{ $landlord ?? '' }}</div>
                            <div class="sign-line-box" style="margin-top: 5px; height: 30px;"></div>
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Footer -->
        <div class="page-footer">
            Plot 32/33, Bombo Road, Kawempe. P.O. Box 12270, Kampala Uganda &nbsp;|&nbsp;
            Tel: +256 204 001 000 &nbsp;|&nbsp; Email: info@harissint.com &nbsp;|&nbsp; Website: www.harissint.com
        </div>
    </div>
</body>

</html>