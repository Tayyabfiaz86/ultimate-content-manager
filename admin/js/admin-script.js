document.addEventListener('DOMContentLoaded', function() {
    const typeSelect = document.getElementById('type');
    const surveyTypeSelect = document.getElementById('survey-type');
    const testTypeSelect = document.getElementById('test-type');
    const surveyTypeFields = document.getElementById('survey-type-fields');
    const testTypeFields = document.getElementById('test-type-fields');
    const surveyFields = document.getElementById('survey-fields');
    const testFields = document.getElementById('test-fields');
    const addSurveyQuestionBtn = document.getElementById('add-survey-question');
    const addTestQuestionBtn = document.getElementById('add-test-question');
    const form = document.querySelector('.custom-survey-form');

    function getFormErrorBox() {
        if (typeSelect.value === 'survey') {
            return document.getElementById('ucm-form-error-survey');
        }
        if (typeSelect.value === 'test') {
            return document.getElementById('ucm-form-error-test');
        }
        return null;
    }

    function clearValidationState() {
        document.querySelectorAll('.ucm-input-error').forEach(el => el.classList.remove('ucm-input-error'));
        document.querySelectorAll('.ucm-question-error').forEach(el => el.classList.remove('ucm-question-error'));
        document.querySelectorAll('.ucm-form-error').forEach(box => {
            box.textContent = '';
            box.style.display = 'none';
        });
    }

    function markInvalidField(field) {
        if (!field) {
            return;
        }
        field.classList.add('ucm-input-error');
        const card = field.closest('.survey-question, .test-question');
        if (card) {
            card.classList.add('ucm-question-error');
        }
    }

    function showFormErrors(messages) {
        if (!messages.length) {
            return;
        }

        const summary = messages.length > 1
            ? 'Please fix ' + messages.length + ' problems below.'
            : 'Please fix the problem below.';

        const errorBox = getFormErrorBox();
        if (errorBox) {
            errorBox.innerHTML = '<strong>' + summary + '</strong><br>' + messages.map(msg => '• ' + msg).join('<br>');
            errorBox.style.display = 'block';
        } else {
            alert(summary + '\n' + messages.join('\n'));
        }
    }

    function validateForm(event) {
        clearValidationState();

        let valid = true;
        let firstInvalid = null;
        const errors = [];
        const name = document.getElementById('name');
        if (!name.value.trim()) {
            valid = false;
            markInvalidField(name);
            firstInvalid = firstInvalid || name;
            errors.push('Name is required.');
        }

        const type = typeSelect.value;
        if (type !== 'survey' && type !== 'test') {
            valid = false;
            markInvalidField(typeSelect);
            firstInvalid = firstInvalid || typeSelect;
            errors.push('Choose Type is required.');
        }

        const questionCards = document.querySelectorAll(type === 'survey' ? '.survey-question' : '.test-question');
        if (questionCards.length === 0) {
            valid = false;
            errors.push('Add at least one question before creating.');
        }

        questionCards.forEach((card, index) => {
            const questionInput = card.querySelector(type === 'survey' ? 'input[name="survey_questions[]"]' : 'input[name="test_questions[]"]');
            if (!questionInput || !questionInput.value.trim()) {
                valid = false;
                markInvalidField(questionInput);
                firstInvalid = firstInvalid || questionInput;
                errors.push('Question ' + (index + 1) + ' is required.');
            }

            const subtype = type === 'survey' ? surveyTypeSelect.value : testTypeSelect.value;
            const choicesVisible = subtype === 'multiple_choices';
            if (choicesVisible) {
                const choiceInputs = card.querySelectorAll(type === 'survey' ? 'input[name="survey_choices[]"]' : 'input[name="test_choices[]"]');
                choiceInputs.forEach((choiceInput, choiceIndex) => {
                    if (!choiceInput.value.trim()) {
                        valid = false;
                        markInvalidField(choiceInput);
                        firstInvalid = firstInvalid || choiceInput;
                        errors.push('Choice ' + (choiceIndex + 1) + ' for question ' + (index + 1) + ' is required.');
                    }
                });

                if (type === 'test') {
                    const correctSelect = card.querySelector('select[name="test_correct[]"]');
                    if (!correctSelect || !correctSelect.value) {
                        valid = false;
                        markInvalidField(correctSelect);
                        firstInvalid = firstInvalid || correctSelect;
                        errors.push('Correct Answer for question ' + (index + 1) + ' is required.');
                    }
                }
            }
        });

        if (!valid) {
            event.preventDefault();
            showFormErrors(Array.from(new Set(errors)));
            if (firstInvalid) {
                firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                firstInvalid.focus({ preventScroll: true });
            }
        }
    }

    if (form) {
        form.addEventListener('submit', validateForm);
    }

    typeSelect.addEventListener('change', function() {
        if (this.value === 'survey') {
            surveyTypeFields.style.display = 'block';
            testTypeFields.style.display = 'none';
            surveyFields.style.display = 'block';
            testFields.style.display = 'none';
        } else if (this.value === 'test') {
            surveyTypeFields.style.display = 'none';
            testTypeFields.style.display = 'block';
            surveyFields.style.display = 'none';
            testFields.style.display = 'block';
        } else {
            surveyTypeFields.style.display = 'none';
            testTypeFields.style.display = 'none';
            surveyFields.style.display = 'none';
            testFields.style.display = 'none';
        }
    });

    surveyTypeSelect.addEventListener('change', function() {
        const selectedSurveyType = this.value;
        const surveyQuestions = document.querySelectorAll('.survey-question');
        surveyQuestions.forEach(question => {
            const choicesContainer = question.querySelector('.survey-choices-container');
            if (selectedSurveyType === 'multiple_choices') {
                choicesContainer.style.display = 'block';
            } else {
                choicesContainer.style.display = 'none';
            }
        });
    });

    testTypeSelect.addEventListener('change', function() {
        const selectedTestType = this.value;
        const testQuestions = document.querySelectorAll('.test-question');
        testQuestions.forEach(question => {
            const choicesContainer = question.querySelector('.test-choices-container');
            if (selectedTestType === 'multiple_choices') {
                choicesContainer.style.display = 'block';
            } else {
                choicesContainer.style.display = 'none';
            }
        });
    });

    addSurveyQuestionBtn.addEventListener('click', function() {
        const container = document.getElementById('survey-questions-container');
        const selectedSurveyType = surveyTypeSelect.value;
        const question = document.createElement('div');
        question.className = 'survey-question';
        question.innerHTML = `
            <button type="button" class="ucm-remove-question" aria-label="Remove question">×</button>
            <div>
                <label>Question:</label>
                <input type="text" name="survey_questions[]" class="regular-text" />
            </div>
            <div class="survey-choices-container" style="display:${selectedSurveyType === 'multiple_choices' ? 'block' : 'none'};">
                <div class="choice-container">
                    <label>Choice 1:</label>
                    <input type="text" name="survey_choices[]" class="regular-text" />
                </div>
                <div class="choice-container">
                    <label>Choice 2:</label>
                    <input type="text" name="survey_choices[]" class="regular-text" />
                </div>
                <div class="choice-container">
                    <label>Choice 3:</label>
                    <input type="text" name="survey_choices[]" class="regular-text" />
                </div>
                <div class="choice-container">
                    <label>Choice 4:</label>
                    <input type="text" name="survey_choices[]" class="regular-text" />
                </div>
            </div>
        `;
        container.appendChild(question);
    });

    addTestQuestionBtn.addEventListener('click', function() {
        const container = document.getElementById('test-questions-container');
        const selectedTestType = testTypeSelect.value;
        const question = document.createElement('div');
        question.className = 'test-question';
        question.innerHTML = `
            <button type="button" class="ucm-remove-question" aria-label="Remove question">×</button>
            <div>
                <label>Question:</label>
                <input type="text" name="test_questions[]" class="regular-text" />
            </div>
            <div class="test-choices-container" style="display:${selectedTestType === 'multiple_choices' ? 'block' : 'none'};">
                <div class="choice-container">
                    <label>Choice 1:</label>
                    <input type="text" name="test_choices[]" class="regular-text" />
                </div>
                <div class="choice-container">
                    <label>Choice 2:</label>
                    <input type="text" name="test_choices[]" class="regular-text" />
                </div>
                <div class="choice-container">
                    <label>Choice 3:</label>
                    <input type="text" name="test_choices[]" class="regular-text" />
                </div>
                <div class="choice-container">
                    <label>Choice 4:</label>
                    <input type="text" name="test_choices[]" class="regular-text" />
                </div>
                <div class="choice-container">
                    <label>Correct Answer:</label>
                    <select name="test_correct[]" class="regular-text">
                        <option value="" selected disabled>Choose Correct Answer</option>
                        <option value="1">Choice 1</option>
                        <option value="2">Choice 2</option>
                        <option value="3">Choice 3</option>
                        <option value="4">Choice 4</option>
                    </select>
                </div>
            </div>
        `;
        container.appendChild(question);
    });

    // Delegate remove action for dynamically added question cards
    document.addEventListener('click', function(e) {
        if (e.target && e.target.classList && e.target.classList.contains('ucm-remove-question')) {
            var card = e.target.closest('.survey-question, .test-question');
            if (card) card.remove();
        }
    });

    // Trigger change event to set initial visibility
    typeSelect.dispatchEvent(new Event('change'));
});

//// 
document.addEventListener('DOMContentLoaded', function() {
    var modal = document.getElementById('ucm-modal');
    var modalContent = document.getElementById('ucm-modal-body');
    var closeBtn = document.getElementsByClassName('ucm-close')[0];

    document.querySelectorAll('.view-button').forEach(function(button) {
        button.addEventListener('click', function() {
            var resultId = this.getAttribute('data-result-id');
            fetch(ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'ucm_fetch_details',
                    result_id: resultId
                })
            })
            .then(response => response.text())
            .then(data => {
                console.log(data); // Print the data to the console
                if (data.trim() === '') {
                    modalContent.innerHTML = '<p>No data found.</p>';
                } else {
                    modalContent.innerHTML = data;
                }
                modal.style.display = 'block';
            })
            .catch(error => {
                console.error('Error fetching data:', error);
                modalContent.innerHTML = '<p>Error fetching data.</p>';
                modal.style.display = 'block';
            });
        });
    });

    closeBtn.onclick = function() {
        modal.style.display = 'none';
    }

    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    }
});








document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('summary-survey-modal');
    const closeModal = document.querySelector('#summary-survey-modal .close');
    const form = document.getElementById('summary-survey-form');
    const summaryField = document.getElementById('modal-summary');

    if (!modal) {
        console.error('Modal element not found');
        return;
    }

    // Open modal on button click
    document.querySelectorAll('.add-summary').forEach(button => {
        button.addEventListener('click', function() {
            const surveyId = this.getAttribute('data-survey-id');
            document.getElementById('modal-survey-id').value = surveyId;

            // Fetch existing summary
            fetch('/wp-json/ucm/v1/summary', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ survey_id: surveyId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.summary) {
                    summaryField.value = data.summary;
                } else {
                    summaryField.value = '';
                }
                modal.style.display = 'flex'; // Ensure modal is displayed as flex
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while fetching the summary.');
            });
        });
    });

    // Close modal on close button click
    closeModal.addEventListener('click', function() {
        modal.style.display = 'none';
    });

    // Close modal when clicking outside of the modal content
    window.addEventListener('click', function(event) {
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    });

    // Handle form submission
    form.addEventListener('submit', function(event) {
        event.preventDefault();

        const formData = new FormData(this);
        const jsonData = JSON.stringify(Object.fromEntries(formData));

        fetch('/wp-json/ucm/v1/summary', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: jsonData
        })
        .then(response => response.json())
        .then(data => {
            alert(data.message || 'Failed to add summary.');
            if (data.message === 'Summary added successfully.') {
                modal.style.display = 'none';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred.');
        });
    });
});





/* Test question anser results */
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('test-question-answer-results-modal');
    const closeModal = document.querySelector('#test-question-answer-results-modal .close');
    const surveyDetails = document.getElementById('test-question-answer-results-details');
    const modalHeader = document.querySelector('#test-question-answer-results-modal .modal-header h2');

    let totalQuestions = 0;
    let correctAnswers = 0;
    let incorrectAnswers = 0;

    // Open modal on button click
    document.querySelectorAll('.view-details').forEach(button => {
        button.addEventListener('click', function() {
            const surveyId = this.getAttribute('data-survey-id');
            console.log('Survey ID:', surveyId);

            // Fetch survey details using Fetch API
            fetch('/wp-json/ucm/v1/survey-details', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ survey_id: surveyId })
            })
            .then(response => response.json())
            .then(data => {
                console.log('Response data:', data);
                console.log('Number of questions fetched:', data.questions.length);
                surveyDetails.innerHTML = '';

                let totalQuestions = data.questions.length;
                let correctAnswers = 0;
                let incorrectAnswers = 0;

                data.questions.forEach(question => {
                    console.log('Question:', question.question);
                    console.log('Number of answers for question ID', question.question_id, ':', question.answers.length);

                    const questionElement = document.createElement('div');
                    questionElement.classList.add('question');
                    questionElement.innerHTML = `<h3>${question.question}</h3>`;

                    question.answers.forEach(answer => {
                        console.log('Answer:', answer); // Log answer
                        const answerElement = document.createElement('div');
                        answerElement.classList.add('answer');
                        answerElement.textContent = answer.text;

                        // Check if the answer is correct or incorrect
                        const resultText = document.createElement('span');
                        resultText.classList.add('result-text');
                        if (answer.is_correct == '1') {
                            resultText.textContent = ' (Correct)';
                            resultText.style.color = 'green';
                            correctAnswers++;
                        } else if (answer.is_correct == '2') {
                            resultText.textContent = ' (Incorrect)';
                            resultText.style.color = 'red';
                            incorrectAnswers++;
                        }
                        answerElement.appendChild(resultText);

                        // Add buttons for marking correct or incorrect
                        const correctButton = document.createElement('button');
                        correctButton.textContent = 'Correct';
                        correctButton.classList.add('correct-btn');
                        correctButton.addEventListener('click', function() {
                            storeAnswerResult(question.question_id, answer.text, 1);
                        });

                        const incorrectButton = document.createElement('button');
                        incorrectButton.textContent = 'Incorrect';
                        incorrectButton.classList.add('incorrect-btn');
                        incorrectButton.addEventListener('click', function() {
                            storeAnswerResult(question.question_id, answer.text, 2);
                        });

                        const buttonContainer = document.createElement('div');
                        buttonContainer.classList.add('button-container');
                        buttonContainer.appendChild(correctButton);
                        buttonContainer.appendChild(incorrectButton);

                        answerElement.appendChild(buttonContainer);
                        questionElement.appendChild(answerElement);
                    });

                    surveyDetails.appendChild(questionElement);
                });

                // Update the modal header with the summary
                modalHeader.innerHTML = `
                    <span style="color: blue;">Total Questions: ${totalQuestions}</span> | 
                    <span style="color: green;">Correct Answers: ${correctAnswers}</span> | 
                    <span style="color: red;">Incorrect Answers: ${incorrectAnswers}</span>
                `;

                modal.style.display = 'block'; // Use 'block' to display the modal
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while fetching the survey details.');
            });
        });
    });

    // Close modal on close button click
    closeModal.addEventListener('click', function() {
        modal.style.display = 'none';
    });

    // Close modal when clicking outside of the modal content
    window.addEventListener('click', function(event) {
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    });

    // Function to store answer result
    function storeAnswerResult(questionId, answer, result) {
        console.log(`Storing result for question ID ${questionId}, answer: ${answer}, result: ${result}`);

        fetch('/wp-json/ucm/v1/update-answer-result', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                question_id: questionId,
                answer: answer,
                is_correct: result
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Answer result updated successfully.');
            
                // Update the header values
                if (result === 1) {
                    correctAnswers++;
                    incorrectAnswers--;
                } else if (result === 2) {
                    correctAnswers--;
                    incorrectAnswers++;
                }
            
                modalHeader.innerHTML = `
                    <span style="color: blue;">Total Questions: ${totalQuestions}</span> | 
                    <span style="color: green;">Correct Answers: ${correctAnswers}</span> | 
                    <span style="color: red;">Incorrect Answers: ${incorrectAnswers}</span>
                `;
            
                // Update the status text
                const resultText = event.target.closest('.answer').querySelector('.result-text');
                if (result === 1) {
                    resultText.textContent = ' (Correct)';
                    resultText.style.color = 'green';
                } else if (result === 2) {
                    resultText.textContent = ' (Incorrect)';
                    resultText.style.color = 'red';
                }
            } else {
                alert('Failed to update the answer result.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while updating the answer result.');
        });
    }
});