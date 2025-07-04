import React, { useState, useCallback, useEffect } from 'react';
import { useDrag, useDrop } from 'react-dnd';
import { HTML5Backend } from 'react-dnd-html5-backend';
import { DndProvider } from 'react-dnd';

const ItemTypes = {
    QUESTION: 'question',
};

const QuestionCard = ({ question, index, onMove, onQuestionChange, onRemoveQuestion }) => {
    const ref = React.useRef(null);
    const [, drop] = useDrop({
        accept: ItemTypes.QUESTION,
        hover(item, monitor) {
            if (!ref.current) {
                return;
            }
            const dragIndex = item.index;
            const hoverIndex = index;

            if (dragIndex === hoverIndex) {
                return;
            }

            const hoverBoundingRect = ref.current.getBoundingClientRect();
            const hoverMiddleY = (hoverBoundingRect.bottom - hoverBoundingRect.top) / 2;
            const clientOffset = monitor.getClientOffset();
            const hoverClientY = clientOffset.y - hoverBoundingRect.top;

            if (dragIndex < hoverIndex && hoverClientY < hoverMiddleY) {
                return;
            }

            if (dragIndex > hoverIndex && hoverClientY > hoverMiddleY) {
                return;
            }

            onMove(dragIndex, hoverIndex);

            item.index = hoverIndex;
        },
    });

    const [{ isDragging }, drag] = useDrag({
        type: ItemTypes.QUESTION,
        item: () => ({ id: question.id, index }),
        collect: (monitor) => ({
            isDragging: monitor.isDragging(),
        }),
    });

    const opacity = isDragging ? 0 : 1;
    drag(drop(ref));

    const renderQuestionTypeSpecificFields = () => {
        switch (question.type) {
            case 'string':
                return <small className="text-muted">Single-line text</small>;
            case 'text':
                return <small className="text-muted">Multi-line text</small>;
            case 'int':
                return <small className="text-muted">Non-negative integer</small>;
            case 'checkbox':
                return <small className="text-muted">Checkbox</small>;
            default:
                return null;
        }
    };

    return (
        <div ref={ref} className="card mb-2 draggable-question" style={{ opacity }}>
            <div className="card-body">
                <div className="d-flex justify-content-between align-items-center mb-2">
                    <h5 className="card-title mb-0">{question.title || `New ${question.type} Question`}</h5>
                    <button type="button" className="btn-close" aria-label="Remove question" onClick={() => onRemoveQuestion(question.id)}></button>
                </div>
                <div className="mb-2">
                    <label className="form-label visually-hidden">Question Title</label>
                    <input
                        type="text"
                        className="form-control form-control-sm"
                        placeholder="Question Title"
                        value={question.title}
                        onChange={(e) => onQuestionChange(question.id, 'title', e.target.value)}
                        required
                    />
                </div>
                <div className="mb-2">
                    <label className="form-label visually-hidden">Question Description</label>
                    <textarea
                        className="form-control form-control-sm"
                        placeholder="Description (optional)"
                        value={question.description}
                        onChange={(e) => onQuestionChange(question.id, 'description', e.target.value)}
                        rows="2"
                    ></textarea>
                </div>
                <div className="form-check mb-2">
                    <input
                        className="form-check-input"
                        type="checkbox"
                        id={`showInTable-${question.id}`}
                        checked={question.showInTable}
                        onChange={(e) => onQuestionChange(question.id, 'showInTable', e.target.checked)}
                    />
                    <label className="form-check-label" htmlFor={`showInTable-${question.id}`}>
                        Display in results table
                    </label>
                </div>
                {renderQuestionTypeSpecificFields()}
            </div>
        </div>
    );
};

const DraggableQuestionList = ({ questions, onReorder, onQuestionChange, onRemoveQuestion }) => {
    const [cards, setCards] = useState(questions);

    useEffect(() => {
        setCards(questions);
    }, [questions]);

    const moveCard = useCallback((dragIndex, hoverIndex) => {
        const dragCard = cards[dragIndex];
        const newCards = [...cards];
        newCards.splice(dragIndex, 1);
        newCards.splice(hoverIndex, 0, dragCard);
        setCards(newCards);
        onReorder(newCards);
    }, [cards, onReorder]);

    const renderCard = useCallback((card, index) => {
        return (
            <QuestionCard
                key={card.id}
                index={index}
                question={card}
                onMove={moveCard}
                onQuestionChange={onQuestionChange}
                onRemoveQuestion={onRemoveQuestion}
            />
        );
    }, [moveCard, onQuestionChange, onRemoveQuestion]);

    return (
        <DndProvider backend={HTML5Backend}>
            <div className="list-group">
                {cards.map((card, i) => renderCard(card, i))}
            </div>
        </DndProvider>
    );
};

export default DraggableQuestionList;