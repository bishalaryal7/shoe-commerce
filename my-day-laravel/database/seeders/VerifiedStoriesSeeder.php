<?php

namespace Database\Seeders;

use App\Models\Article;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VerifiedStoriesSeeder extends Seeder
{
    public function run(): void
    {
        $stories = [
            ['title' => 'Scientists find a new kind of X-ray object', 'category' => 'Science', 'source_name' => 'NASA', 'source_url' => 'https://science.nasa.gov/missions/chandra/nasas-chandra-unveils-mysterious-x-ray-objects/', 'source_published_at' => '2026-09-09', 'summary' => 'Observations from the Chandra X-ray Observatory have revealed a group of unusual objects in other galaxies.', 'body' => "Astronomers using NASA’s Chandra X-ray Observatory have identified objects that emit relatively low-energy X-rays alongside strong ultraviolet radiation. Their behavior does not match objects previously known to the researchers.\n\nThe team is investigating what these sources might be and whether they could help answer questions about the lives of stars and other objects in distant galaxies. The findings were published in Nature Astronomy.\n\nThis summary is based on NASA’s account. Follow the source link for the full report and its images."],
            ['title' => 'Webb telescope confirms a remarkably early galaxy', 'category' => 'Science', 'source_name' => 'NASA', 'source_url' => 'https://science.nasa.gov/missions/webb/nasa-webb-pushes-boundaries-of-observable-universe-closer-to-big-bang/', 'source_published_at' => '2026-01-28', 'summary' => 'The James Webb Space Telescope confirmed a bright galaxy seen as it was 280 million years after the big bang.', 'body' => "NASA reports that the James Webb Space Telescope has confirmed a galaxy known as MoM-z14, observed as it appeared when the universe was around 280 million years old.\n\nLooking at very distant galaxies gives astronomers a window into the universe’s early history. The observation helps researchers study how galaxies formed in the first few hundred million years after the big bang.\n\nRead NASA’s report for the full details and images."],
            ['title' => 'Researchers study fitness and brain health across adulthood', 'category' => 'Wellbeing', 'source_name' => 'National Institute on Aging', 'source_url' => 'https://www.nia.nih.gov/news/lifelong-exercise-promotes-brain-health-older-adults', 'source_published_at' => '2025-01-08', 'summary' => 'A study of 125 adults examined the relationship between cardiorespiratory fitness and the brain’s white matter.', 'body' => "Researchers supported by the National Institute on Aging assessed cardiorespiratory fitness and brain scans from 125 adults aged 22 to 94 who did not have cognitive impairment. They looked at myelin, the insulating material around nerve fibers in the brain.\n\nThe researchers reported an association between fitness and measures of brain health. This study does not establish that exercise prevents dementia or guarantee an outcome for an individual.\n\nThe institute’s report links to the underlying research for readers who want more detail."],
            ['title' => 'United Nations highlights older people as community leaders', 'category' => 'Community', 'source_name' => 'United Nations', 'source_url' => 'https://www.un.org/en/events/olderpersonsday/index.shtml', 'source_published_at' => '2025-10-01', 'summary' => 'The UN’s 2025 International Day of Older Persons focused on older people’s contributions and rights.', 'body' => "The United Nations’ 2025 International Day of Older Persons focused on the role older people play in local and global action. Its program highlighted their contributions to community resilience, health equity, financial wellbeing and human rights.\n\nThe UN also emphasized participation in decisions and policies that affect older people. Its event page provides information about the commemoration and the broader international conversation on ageing.\n\nVisit the source for the UN’s full account."],
            ['title' => 'NASA investigates a possible collision of neutron stars', 'category' => 'Science', 'source_name' => 'NASA', 'source_url' => 'https://www.nasa.gov/missions/chandra/nasa-discovers-crash-of-extreme-stars-in-unexpected-site/', 'source_published_at' => '2026-03-10', 'summary' => 'Multiple space telescopes helped scientists investigate an explosive event in an unexpected setting.', 'body' => "NASA reports that observations from several space missions may have revealed a collision between two neutron stars in a small galaxy embedded in a large stream of gas. Neutron stars are extremely dense remnants of massive stars.\n\nThe researchers used data from missions including Chandra, Fermi, Swift and Hubble to study the event. The interpretation remains a scientific finding under investigation rather than a certainty.\n\nNASA’s article describes the observations and links to the published research."],
        ];
        DB::transaction(function () use ($stories) {
            // Preserve earlier samples and reader bookmarks while removing them from the public feed.
            Article::where('is_sample', true)->update(['published' => false]);
            foreach ($stories as $story) {
                Article::firstOrCreate(['source_url' => $story['source_url']], $story + ['published' => true, 'is_sample' => false]);
            }
        });
    }
}
