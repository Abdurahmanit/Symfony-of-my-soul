import React, { useState, useEffect } from 'react';
import { createRoot } from 'react-dom/client';
import DraggableQuestion from './components/DraggableQuestion';
import AutocompleteUserSelect from './components/AutocompleteUserSelect';

function TemplateFormApp() {
    const [questions, setQuestions] = useState([]);
    const [templateData, setTemplateData] = useState({
        title: '',
        description: '',
        topic: '',
        imageUrl: '',
        tags: [],
        accessType: 'public',
        restrictedUsers: []
    });

    useEffect(() => {
        const rootElement = document.getElementById('template-form-root');
        if (rootElement && rootElement.dataset.initialTemplate) {
            const initialTemplate = JSON.parse(rootElement.dataset.initialTemplate);
            setTemplateData(initialTemplate.general || {});
            setQuestions(initialTemplate.questions || []);
        }
    }, []);

    const handleQuestionChange = (id, field, value) => {
        setQuestions(questions.map(q => q.id === id ? { ...q, [field]: value } : q));
    };

    const addQuestion = (type) => {
        const newId = Math.max(...questions.map(q => q.id), 0) + 1;
        const newQuestion = {
            id: newId,
            type: type,
            title: '',
            description: '',
            showInTable: false
        };
        setQuestions([...questions, newQuestion]);
    };

    const removeQuestion = (id) => {
        setQuestions(questions.filter(q => q.id !== id));
    };

    const handleTemplateDataChange = (e) => {
        const { name, value, type, checked } = e.target;
        setTemplateData(prev => ({
            ...prev,
            [name]: type === 'checkbox' ? checked : value
        }));
    };

    const handleTagsChange = (newTags) => {
        setTemplateData(prev => ({ ...prev, tags: newTags }));
    };

    const handleRestrictedUsersChange = (users) => {
        setTemplateData(prev => ({ ...prev, restrictedUsers: users }));
    };

    const handleQuestionReorder = (newOrder) => {
        setQuestions(newOrder);
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        const formData = {
            general: templateData,
            questions: questions
        };
        console.log("Submitting form data:", formData);
    };

    return (
        <div className="container mt-4">
            <h1>Create/Edit Template</h1>
            <form onSubmit={handleSubmit}>
                <div className="form-section">
                    <h3>General Settings</h3>
                    <div className="mb-3">
                        <label htmlFor="title" className="form-label">Title</label>
                        <input type="text" className="form-control" id="title" name="title" value={templateData.title} onChange={handleTemplateDataChange} required />
                    </div>
                    <div className="mb-3">
                        <label htmlFor="description" className="form-label">Description (Markdown supported)</label>
                        <textarea className="form-control" id="description" name="description" value={templateData.description} onChange={handleTemplateDataChange} rows="5"></textarea>
                    </div>
                    <div className="mb-3">
                        <label htmlFor="topic" className="form-label">Topic</label>
                        <select className="form-select" id="topic" name="topic" value={templateData.topic} onChange={handleTemplateDataChange} required>
                            <option value="">Select a topic</option>
                            <option value="Education">Education</option>
                            <option value="Quiz">Quiz</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div className="mb-3">
                        <label htmlFor="imageUrl" className="form-label">Image URL</label>
                        <input type="text" className="form-control" id="imageUrl" name="imageUrl" value={templateData.imageUrl} onChange={handleTemplateDataChange} placeholder="Enter Cloudinary image URL" />
                    </div>
                    <div className="mb-3">
                        <label className="form-label">Tags</label>
                        <input type="text" className="form-control" placeholder="Add tags" />
                        <div className="d-flex flex-wrap gap-2 mt-2">
                            {templateData.tags.map((tag, index) => (
                                <span key={index} className="badge bg-primary d-flex align-items-center">
                                    {tag}
                                    <button type="button" className="btn-close btn-close-white ms-1" aria-label="Remove tag"></button>
                                </span>
                            ))}
                        </div>
                    </div>
                    <div className="mb-3">
                        <label className="form-label">Access Settings</label>
                        <div className="form-check">
                            <input className="form-check-input" type="radio" name="accessType" id="accessPublic" value="public" checked={templateData.accessType === 'public'} onChange={handleTemplateDataChange} />
                            <label className="form-check-label" htmlFor="accessPublic">
                                Public
                            </label>
                        </div>
                        <div className="form-check">
                            <input className="form-check-input" type="radio" name="accessType" id="accessRestricted" value="restricted" checked={templateData.accessType === 'restricted'} onChange={handleTemplateDataChange} />
                            <label className="form-check-label" htmlFor="accessRestricted">
                                Restricted
                            </label>
                        </div>
                    </div>
                    {templateData.accessType === 'restricted' && (
                        <div className="mb-3">
                            <label className="form-label">Select Users</label>
                            <AutocompleteUserSelect
                                initialUsers={templateData.restrictedUsers}
                                onUsersChange={handleRestrictedUsersChange}
                                inputName="restrictedUsers[]"
                            />
                        </div>
                    )}
                </div>

                <div className="form-section mt-4">
                    <h3>Questions</h3>
                    <p>Drag and drop to reorder questions.</p>
                    <DraggableQuestion questions={questions} onReorder={handleQuestionReorder} onQuestionChange={handleQuestionChange} onRemoveQuestion={removeQuestion} />

                    <div className="d-flex gap-2 mt-3">
                        <button type="button" className="btn btn-outline-primary" onClick={() => addQuestion('string')}>Add Single-line String</button>
                        <button type="button" className="btn btn-outline-primary" onClick={() => addQuestion('text')}>Add Multi-line Text</button>
                        <button type="button" className="btn btn-outline-primary" onClick={() => addQuestion('int')}>Add Non-negative Integer</button>
                        <button type="button" className="btn btn-outline-primary" onClick={() => addQuestion('checkbox')}>Add Checkbox</button>
                    </div>
                </div>

                <button type="submit" className="btn btn-success mt-4">Save Template</button>
            </form>
        </div>
    );
}

export default TemplateFormApp;