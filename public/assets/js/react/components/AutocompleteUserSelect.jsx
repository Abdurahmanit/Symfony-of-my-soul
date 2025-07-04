import React, { useState, useEffect, useRef } from 'react';

function AutocompleteUserSelect({ initialUsers = [], onUsersChange, inputName }) {
    const [searchTerm, setSearchTerm] = useState('');
    const [suggestions, setSuggestions] = useState([]);
    const [selectedUsers, setSelectedUsers] = useState(initialUsers);
    const [highlightedIndex, setHighlightedIndex] = useState(-1);
    const inputRef = useRef(null);
    const dropdownRef = useRef(null);

    useEffect(() => {
        const fetchSuggestions = async () => {
            if (searchTerm.length < 2) {
                setSuggestions([]);
                return;
            }

            try {
                const response = await fetch(`/api/users/autocomplete?query=${encodeURIComponent(searchTerm)}`);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                const data = await response.json();
                const filteredSuggestions = data.filter(
                    (user) => !selectedUsers.some((selected) => selected.id === user.id)
                );
                setSuggestions(filteredSuggestions);
                setHighlightedIndex(-1);
            } catch (error) {
                console.error("Error fetching user suggestions:", error);
                setSuggestions([]);
            }
        };

        const handler = setTimeout(() => {
            fetchSuggestions();
        }, 300);

        return () => {
            clearTimeout(handler);
        };
    }, [searchTerm, selectedUsers]);

    useEffect(() => {
        onUsersChange(selectedUsers);
    }, [selectedUsers, onUsersChange]);

    const handleInputChange = (e) => {
        setSearchTerm(e.target.value);
    };

    const handleSelectUser = (user) => {
        setSelectedUsers([...selectedUsers, user]);
        setSearchTerm('');
        setSuggestions([]);
        inputRef.current.focus();
    };

    const handleRemoveUser = (userId) => {
        setSelectedUsers(selectedUsers.filter((user) => user.id !== userId));
    };

    const handleKeyDown = (e) => {
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            setHighlightedIndex((prevIndex) =>
                Math.min(prevIndex + 1, suggestions.length - 1)
            );
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            setHighlightedIndex((prevIndex) =>
                Math.max(prevIndex - 1, 0)
            );
        } else if (e.key === 'Enter' && highlightedIndex !== -1) {
            e.preventDefault();
            handleSelectUser(suggestions[highlightedIndex]);
        } else if (e.key === 'Escape') {
            setSuggestions([]);
        } else if (e.key === 'Backspace' && searchTerm === '' && selectedUsers.length > 0) {
            handleRemoveUser(selectedUsers[selectedUsers.length - 1].id);
        }
    };

    useEffect(() => {
        const handleClickOutside = (event) => {
            if (dropdownRef.current && !dropdownRef.current.contains(event.target) &&
                inputRef.current && !inputRef.current.contains(event.target)) {
                setSuggestions([]);
            }
        };
        document.addEventListener('mousedown', handleClickOutside);
        return () => {
            document.removeEventListener('mousedown', handleClickOutside);
        };
    }, []);

    return (
        <div>
            <div className="tag-input-container">
                {selectedUsers.map((user) => (
                    <span key={user.id} className="tag-badge bg-secondary">
                        {user.name} ({user.email})
                        <button
                            type="button"
                            className="btn-close btn-close-white ms-1"
                            aria-label="Remove user"
                            onClick={() => handleRemoveUser(user.id)}
                        ></button>
                        <input type="hidden" name={inputName} value={user.id} />
                    </span>
                ))}
                <input
                    ref={inputRef}
                    type="text"
                    className="form-control flex-grow-1"
                    placeholder="Search users by name or email..."
                    value={searchTerm}
                    onChange={handleInputChange}
                    onKeyDown={handleKeyDown}
                />
            </div>
            {suggestions.length > 0 && (
                <div className="autocomplete-dropdown mt-1" ref={dropdownRef}>
                    {suggestions.map((user, index) => (
                        <div
                            key={user.id}
                            className={`autocomplete-item ${index === highlightedIndex ? 'active' : ''}`}
                            onClick={() => handleSelectUser(user)}
                        >
                            {user.name} ({user.email})
                        </div>
                    ))}
                </div>
            )}
        </div>
    );
}

export default AutocompleteUserSelect;