// ai_features.js - AI Features for Connect for Peace

window.AI_Features = {
    // Initialize Chatbot
    initChatbot: function () {
        console.log("AI Chatbot Initialized");
        // Placeholder for chatbot initialization logic
        // This would typically involve setting up a chat widget or connecting to a backend service
    },

    // Suggest tags based on content
    suggestTags: function (text) {
        // Simple keyword matching for demo purposes
        const keywords = {
            'peace': 'Peace Building',
            'community': 'Community',
            'help': 'Social Help',
            'education': 'Educational',
            'school': 'Educational',
            'environment': 'Environment',
            'nature': 'Environment',
            'art': 'Art & Culture',
            'music': 'Art & Culture',
            'love': 'Personal',
            'life': 'Personal'
        };

        const suggestions = new Set();
        const lowerText = text.toLowerCase();

        for (const [keyword, tag] of Object.entries(keywords)) {
            if (lowerText.includes(keyword)) {
                suggestions.add(tag);
            }
        }

        return Array.from(suggestions);
    },

    // Check content for inappropriate language
    checkContent: function (text) {
        const flaggedWords = ['hate', 'violence', 'kill', 'abuse', 'stupid', 'idiot']; // Example list
        const lowerText = text.toLowerCase();
        let flagged = false;
        let foundWords = [];

        for (const word of flaggedWords) {
            if (lowerText.includes(word)) {
                flagged = true;
                foundWords.push(word);
            }
        }

        return {
            flagged: flagged,
            reason: flagged ? `Contains inappropriate words: ${foundWords.join(', ')}` : null
        };
    },

    // Analyze sentiment of content
    analyzeSentiment: function (text) {
        const positiveWords = ['love', 'happy', 'great', 'peace', 'hope', 'joy', 'wonderful', 'support'];
        const negativeWords = ['sad', 'angry', 'hate', 'bad', 'terrible', 'pain', 'suffer'];

        let score = 0;
        const lowerText = text.toLowerCase();
        const words = lowerText.split(/\s+/);

        words.forEach(word => {
            if (positiveWords.includes(word)) score++;
            if (negativeWords.includes(word)) score--;
        });

        if (score > 0) return { label: 'Positive', color: 'text-green-600' };
        if (score < 0) return { label: 'Negative', color: 'text-red-600' };
        return { label: 'Neutral', color: 'text-zinc-500' };
    }
};
