<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** The filter buttons of the portfolio page, as an admin-managed Master Data group. */
    private const PORTFOLIO_CATEGORIES = ['analytics' => 'Analytics', 'development' => 'Development', 'training' => 'Training'];

    /** What the public site said before it became editable, so nothing changes until someone edits it. */
    public function up(): void
    {
        $now = now();

        foreach ($this->items() as $type => $rows) {
            foreach ($rows as $order => $row) {
                DB::table('site_items')->insert([
                    'type' => $type, 'sort_order' => $order + 1, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now,
                    'subtitle' => null, 'body' => null, 'icon' => null, 'image_url' => null, 'link_url' => null, 'category' => null, 'number' => null,
                    ...$row,
                ]);
            }
        }

        $order = 0;
        foreach (self::PORTFOLIO_CATEGORIES as $code => $label) {
            DB::table('master_data')->insert([
                'group' => 'portfolio_category', 'code' => $code, 'label' => $label,
                'sort_order' => ++$order, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('site_items')->truncate();
        DB::table('master_data')->where('group', 'portfolio_category')->delete();
    }

    /** @return array<string, list<array<string, mixed>>> */
    private function items(): array
    {
        return [
            'home_service' => [
                ['icon' => '📊', 'title' => 'Business Intelligence', 'body' => 'Executive dashboards, KPI design, strategic insights, and operational reporting.'],
                ['icon' => '💻', 'title' => 'Website & Web Application', 'body' => 'Premium digital experiences and custom web products built for scale.'],
                ['icon' => '🏢', 'title' => 'ERP System', 'body' => 'Future-ready enterprise architecture for operational excellence.'],
                ['icon' => '🎓', 'title' => 'Corporate Training', 'body' => 'Hands-on capability building across Excel, Power BI, SQL, Python, AI, and more.'],
            ],
            'service' => [
                ['icon' => '📊', 'title' => 'Business Intelligence', 'body' => 'Executive dashboards, KPI design, strategic insights, and operational reporting.'],
                ['icon' => '📈', 'title' => 'Power BI Dashboard', 'body' => 'Interactive analytics solutions for modern business performance monitoring.'],
                ['icon' => '🧠', 'title' => 'Data Analytics', 'body' => 'Transform raw data into stories, decisions, and measurable business outcomes.'],
                ['icon' => '🎓', 'title' => 'Corporate Training', 'body' => 'Hands-on capability building across Excel, Power BI, SQL, Python, AI, and more.'],
                ['icon' => '💻', 'title' => 'Website Development', 'body' => 'Premium digital experiences tailored for growth, trust, and conversion.'],
                ['icon' => '🧩', 'title' => 'Web Application', 'body' => 'Custom web products designed for productivity, workflows, and scale.'],
                ['icon' => '🏢', 'title' => 'ERP System', 'body' => 'Future-ready enterprise architecture for operational excellence and integration.'],
                ['icon' => '✨', 'title' => 'AI Solution', 'body' => 'Intelligent automation, assistants, and analytics platforms for modern teams.'],
                ['icon' => '📱', 'title' => 'Mobile Application', 'body' => 'Cross-platform mobile solutions built for speed, reliability, and usability.'],
                ['icon' => '🗄️', 'title' => 'Database Development', 'body' => 'Reliable data models, storage architecture, and high-performance access.'],
                ['icon' => '☁️', 'title' => 'Cloud Solution', 'body' => 'Scalable cloud environments for resilience, collaboration, and modernization.'],
                ['icon' => '🛠️', 'title' => 'IT Consulting', 'body' => 'Technology roadmaps and advisory aligned with strategy, compliance, and growth.'],
            ],
            'training' => [
                ['title' => 'Microsoft Excel', 'body' => 'Advanced formulas, dashboards, and business productivity.'],
                ['title' => 'Microsoft Power BI', 'body' => 'Analytics and visualization for enterprise intelligence.'],
                ['title' => 'Power Query', 'body' => 'Automate and prepare data from multiple systems.'],
                ['title' => 'Power Pivot', 'body' => 'Model and analyze large datasets with confidence.'],
                ['title' => 'SQL', 'body' => 'Query design, data modeling, and reporting workflows.'],
                ['title' => 'Python', 'body' => 'Automation, scripting, analysis, and advanced workflows.'],
                ['title' => 'AI for Office', 'body' => 'Practical AI use cases for productivity and collaboration.'],
                ['title' => 'ChatGPT', 'body' => 'Adopt generative AI safely and effectively.'],
                ['title' => 'Microsoft Copilot', 'body' => 'Boost team productivity with AI-enabled work patterns.'],
                ['title' => 'Google Workspace', 'body' => 'Modern collaboration and secure cloud workplace practices.'],
                ['title' => 'Power Automate', 'body' => 'Automate repetitive work and accelerate service delivery.'],
                ['title' => 'Business Intelligence', 'body' => 'Turn data into strategic action with confidence.'],
            ],
            'stat' => [
                ['title' => 'Training', 'number' => 250],
                ['title' => 'Projects', 'number' => 120],
                ['title' => 'Client Satisfaction', 'number' => 95],
                ['title' => 'Professional Trainers', 'number' => 15],
            ],
            'timeline' => [
                ['title' => '2018', 'body' => 'Founded with a focus on analytics and enterprise solutions.'],
                ['title' => '2020', 'body' => 'Expanded into training and AI-powered consulting.'],
                ['title' => '2024', 'body' => 'Delivered enterprise-grade digital transformation programs.'],
            ],
            'testimonial' => [
                [
                    'title' => 'Indra Aliyudin.', 'subtitle' => 'Maintenance Technician',
                    'body' => 'Pengalaman mengikuti jasa les privat Excel dan Power BI sungguh luar biasa. Pengajar memiliki kemampuan yang mengagumkan dalam mengajar konsep-konsep yang kompleks menjadi lebih mudah dipahami. Saya merasa lebih mahir dalam menggunakan rumus-rumus Excel dan mampu membuat laporan interaktif yang menarik menggunakan Power BI berkat bimbingan yang diberikan. Terima kasih atas kesabaran dan dedikasinya dalam membantu perkembangan kemampuan saya',
                    'image_url' => 'https://lh3.googleusercontent.com/a-/ALV-UjX7qHWb3A5tYZszMhh1erWa7yWbkIGUpraIdruy5zMB_okwwaT7=w36-h36-p-rp-mo-br100',
                    'link_url' => 'https://maps.app.goo.gl/BFpx2DvcWUw64Amu5',
                ],
                [
                    'title' => 'Susi Diah Lestari', 'subtitle' => 'Digital Transformation',
                    'body' => 'ma sya Allah bener bener worth it kursus di alfajar,terimakasih bapak sudah membimbing dengan sangat baik dan sangat sangat sabar semoga ilmu nya bisa saya manfaatkan dengan baik dan semoga rezeki bapak lancar selalu.',
                    'image_url' => 'https://lh3.googleusercontent.com/a-/ALV-UjVySej1VytPaT0lUQrjSzP5TB7PGscaRPCY2XNeHDsqNlb2B1Ec=w72-h72-p-rp-mo-br100',
                    'link_url' => 'https://maps.app.goo.gl/criULXFh8dK5SqVe9',
                ],
                [
                    'title' => 'Flash Celia', 'subtitle' => 'Bisnis Digital',
                    'body' => 'Terima kasih, ilmunya pasti bermanfaat karena saya yg awalnya nol banget dan sekarang sudah bisa mandiri dalam mengolah data di excel hingga mampu membuat visualisasi yg keren. Mantabbbbbbb',
                    'image_url' => 'https://lh3.googleusercontent.com/a/ACg8ocJqmcBdSxSOxGCGLXBuwitvnOyaynodxLJ3YcnQE9pYwBsIXw=w36-h36-p-rp-mo-br100',
                    'link_url' => 'https://maps.app.goo.gl/9PLfSpvcUr28K27n9',
                ],
            ],
            'portfolio' => [
                ['title' => 'Enterprise BI Platform', 'body' => 'Modern dashboards and performance tracking for manufacturing operations.', 'category' => 'analytics', 'image_url' => 'assets/images/portfolio-1.png'],
                ['title' => 'Client Portal Web App', 'body' => 'Secure portal for service workflows, reporting, and user collaboration.', 'category' => 'development', 'image_url' => 'assets/images/portfolio-2.png'],
                ['title' => 'Power BI Upskilling Program', 'body' => 'Training initiative designed to accelerate analyst adoption across teams.', 'category' => 'training', 'image_url' => 'assets/images/portfolio-3.png'],
                ['title' => 'ERP Architecture Design', 'body' => 'Scalable architecture plan for future-ready enterprise operations.', 'category' => 'development', 'image_url' => 'assets/images/portfolio-4.png'],
            ],
            'footer_link' => [
                ['title' => 'BI & Analytics', 'link_url' => '/services'],
                ['title' => 'Software Development', 'link_url' => '/services'],
                ['title' => 'Corporate Training', 'link_url' => '/services#training'],
                ['title' => 'AI Solution', 'link_url' => '/services'],
            ],
        ];
    }
};
